<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Enhanced SENCE Reporting Hub with Group-as-Action-ID Filtering.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use block_sence_connector\util\sence_helper;
use block_sence_connector\task\sync_sic_task;

$courseid = required_param('courseid', PARAM_INT);
$tab = optional_param('tab', 'attendance', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$filter_groupid = optional_param('groupid', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/sence_connector:viewreports', $context);

$is_admin = has_capability('block/sence_connector:viewaudit', $context) || is_siteadmin();

if (($tab === 'synclog' || $tab === 'sessions') && !$is_admin) {
    $tab = 'attendance';
}

$all_enrolled = get_enrolled_users($context, 'block/sence_connector:view');
$groups = groups_get_all_groups($courseid);

// Manual Sync trigger
if ($action === 'sync' && confirm_sesskey()) {
    if (!$is_admin) {
        throw new \moodle_exception('nopermissions', 'error', '', 'Solo administradores pueden forzar envíos al SIC');
    }
    $rutotec = get_config('block_sence_connector', 'rutotec');
    $token = get_config('block_sence_connector', 'token');
    $idsistema = (int)(get_config('block_sence_connector', 'idsistema') ?: 1350);

    sync_sic_task::sync_course_by_groups($courseid, $filter_groupid, $rutotec, $token, $idsistema);

    \core\notification::success("Sincronización con API Gestor Intermedio (SIC) ejecutada exitosamente.");
    redirect(new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'tab' => 'synclog', 'groupid' => $filter_groupid]));
}

// Filter students by selected Moodle Group (ID de Acción)
$display_users = [];
if ($filter_groupid > 0) {
    $group_members = $DB->get_fieldset_select('groups_members', 'userid', 'groupid = :gid', ['gid' => $filter_groupid]);
    foreach ($all_enrolled as $u) {
        if (in_array($u->id, $group_members)) {
            $display_users[] = $u;
        }
    }
} else {
    $display_users = $all_enrolled;
}

// Selected Group label
$selected_group_name = 'Todos los Grupos / IDs de Acción';
if ($filter_groupid > 0 && isset($groups[$filter_groupid])) {
    $selected_group_name = $groups[$filter_groupid]->name;
}

// Export CSV for OTIC / SENCE
if ($action === 'export_csv') {
    $filename = 'Liquidacion_SENCE_Grupo_' . clean_param($selected_group_name, PARAM_ALPHANUMEXT) . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'RUT Alumno',
        'Nombre Completo',
        'Email',
        'Grupo Moodle / ID Accion SENCE',
        'Horas Requeridas',
        'Horas Efectivas Computadas',
        '% Asistencia',
        'Minimo Exigido (%)',
        'Estado SENCE',
        'Sesiones Realizadas',
        'Ultima Conexion'
    ], ';');

    foreach ($display_users as $u) {
        $ua = sence_helper::get_user_action_config($courseid, $u->id);
        $run = sence_helper::get_user_run($u);
        $total_sec = (int)$DB->get_field_sql(
            "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
            ['courseid' => $courseid, 'userid' => $u->id]
        ) ?: 0;
        $hours = round($total_sec / 3600, 2);
        $req_hours = (float)$ua->horastotales;
        $pct = ($req_hours > 0) ? min(100, round(($hours / $req_hours) * 100, 1)) : 0;
        $min_pct = (float)$ua->asistenciaminima;
        $is_approved = ($pct >= $min_pct);
        $sess_count = $DB->count_records('block_sence_sessions', ['courseid' => $courseid, 'userid' => $u->id]);
        $last_conn = $DB->get_field_sql(
            "SELECT MAX(timestart) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid",
            ['courseid' => $courseid, 'userid' => $u->id]
        );

        fputcsv($output, [
            $run ?: 'Sin RUN',
            fullname($u),
            $u->email,
            $ua->codigooferta,
            $req_hours,
            $hours,
            $pct . '%',
            $min_pct . '%',
            $is_approved ? 'APROBADO' : 'CURSANDO',
            $sess_count,
            $last_conn ? date('d/m/Y H:i:s', $last_conn) : 'Sin registros'
        ], ';');
    }
    fclose($output);
    exit;
}

$PAGE->set_url(new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'tab' => $tab, 'groupid' => $filter_groupid]));
$PAGE->set_context($context);
$PAGE->set_title('Centro de Informes y Auditoría SENCE - Keystone Advisors');
$PAGE->set_heading($course->fullname . ' - Centro de Informes SENCE');

echo $OUTPUT->header();

$sync_url = new moodle_url('/blocks/sence_connector/report.php', [
    'courseid' => $courseid,
    'action' => 'sync',
    'groupid' => $filter_groupid,
    'sesskey' => sesskey()
]);
$export_url = new moodle_url('/blocks/sence_connector/report.php', [
    'courseid' => $courseid,
    'action' => 'export_csv',
    'groupid' => $filter_groupid
]);

?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <div>
        <h2 class="font-weight-bold m-0" style="color: #225268;">
            <i class="fa fa-university me-2 mr-2"></i> Centro de Informes y Auditoría SENCE
        </h2>
        <small class="text-muted">
            Curso: <strong><?php echo htmlspecialchars($course->fullname); ?></strong>
            | Perfil: <span class="badge <?php echo $is_admin ? 'bg-primary badge-primary' : 'bg-secondary badge-secondary'; ?>"><?php echo $is_admin ? 'Administrador / Auditor' : 'Docente / Profesor'; ?></span>
        </small>
    </div>
    <div class="mt-2 mt-md-0">
        <a href="<?php echo $export_url->out(false); ?>" class="btn btn-outline-success btn-sm me-2 mr-2">
            <i class="fa fa-file-excel-o me-1 mr-1"></i> Descargar Planilla Liquidación (CSV/Excel)
        </a>
        <?php if ($is_admin): ?>
            <a href="<?php echo $sync_url->out(false); ?>" class="btn btn-primary btn-sm" style="background-color: #225268; border-color: #225268;">
                <i class="fa fa-refresh me-1 mr-1"></i> Sincronizar Ahora con SIC
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtro Selector Directo por Grupo Moodle (ID de Acción) -->
<div class="card bg-light border-0 p-3 mb-4">
    <form method="GET" action="<?php echo $CFG->wwwroot; ?>/blocks/sence_connector/report.php" class="row align-items-center g-2">
        <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
        <div class="col-auto">
            <label class="font-weight-bold mb-0 text-dark">
                <i class="fa fa-users me-1 mr-1"></i> Filtrar por Grupo Moodle (ID Acción / Empresa):
            </label>
        </div>
        <div class="col-md-5">
            <select name="groupid" class="form-select form-control" onchange="this.form.submit()">
                <option value="0" <?php echo ($filter_groupid === 0) ? 'selected' : ''; ?>>
                    — Ver Todos los Grupos / Alumnos del Curso (<?php echo count($all_enrolled); ?> participantes) —
                </option>
                <?php foreach ($groups as $g): 
                    $count_g = $DB->count_records('groups_members', ['groupid' => $g->id]);
                    $selected = ($filter_groupid === (int)$g->id) ? 'selected' : '';
                ?>
                    <option value="<?php echo $g->id; ?>" <?php echo $selected; ?>>
                        Grupo: <?php echo htmlspecialchars($g->name); ?> (<?php echo $count_g; ?> alumnos)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <a href="<?php echo (new moodle_url('/group/index.php', ['id' => $courseid]))->out(false); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-pencil me-1 mr-1"></i> Administrar Grupos en Moodle
            </a>
        </div>
    </form>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo ($tab === 'attendance') ? 'active font-weight-bold' : ''; ?>" href="<?php echo (new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'tab' => 'attendance', 'groupid' => $filter_groupid]))->out(false); ?>">
            <i class="fa fa-users me-1 mr-1"></i> 1. Libro de Asistencia del Curso
        </a>
    </li>
    <?php if ($is_admin): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo ($tab === 'synclog') ? 'active font-weight-bold' : ''; ?>" href="<?php echo (new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'tab' => 'synclog', 'groupid' => $filter_groupid]))->out(false); ?>">
                <i class="fa fa-exchange me-1 mr-1"></i> 2. Bitácora API Gestor Intermedio (SIC)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($tab === 'sessions') ? 'active font-weight-bold' : ''; ?>" href="<?php echo (new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'tab' => 'sessions', 'groupid' => $filter_groupid]))->out(false); ?>">
                <i class="fa fa-shield me-1 mr-1"></i> 3. Registro Forense de Sesiones RCE (Auditoría)
            </a>
        </li>
    <?php else: ?>
        <li class="nav-item">
            <span class="nav-link disabled text-muted" title="Disponible exclusivamente para administradores y auditores del OTEC">
                <i class="fa fa-lock me-1 mr-1"></i> 2. Bitácora SIC (Solo Admin)
            </span>
        </li>
        <li class="nav-item">
            <span class="nav-link disabled text-muted" title="Disponible exclusivamente para administradores y auditores del OTEC">
                <i class="fa fa-lock me-1 mr-1"></i> 3. Auditoría Forense (Solo Admin)
            </span>
        </li>
    <?php endif; ?>
</ul>

<?php
// TAB 1: LIBRO DE ASISTENCIA
if ($tab === 'attendance') {
    $total_enrolled = count($display_users);
    $today_start = strtotime('today midnight');
    $active_today = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT userid) FROM {block_sence_sessions} WHERE courseid = :courseid AND timestart >= :today",
        ['courseid' => $courseid, 'today' => $today_start]
    );
    $last_sync = $DB->get_record_sql(
        "SELECT * FROM {block_sence_synclog} WHERE courseid = :courseid ORDER BY id DESC",
        ['courseid' => $courseid],
        IGNORE_MULTIPLE
    );
    ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-light">
                <span class="text-muted small">Alumnos en Nómina Actual</span>
                <h3 class="font-weight-bold text-dark m-0"><?php echo $total_enrolled; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-light">
                <span class="text-muted small">Conectados Hoy</span>
                <h3 class="font-weight-bold text-success m-0"><?php echo $active_today; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-light">
                <span class="text-muted small">Grupo / ID Acción</span>
                <h4 class="font-weight-bold text-info m-0 text-truncate" title="<?php echo htmlspecialchars($selected_group_name); ?>">
                    <?php echo htmlspecialchars($selected_group_name); ?>
                </h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-light">
                <span class="text-muted small">Último Envío SIC</span>
                <span class="badge <?php echo ($last_sync && $last_sync->success) ? 'bg-success badge-success' : 'bg-secondary badge-secondary'; ?> mt-1">
                    <?php echo $last_sync ? userdate($last_sync->timecreated, '%d/%m/%Y %H:%M') : 'Sin envíos'; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white font-weight-bold">
            <i class="fa fa-list me-1 mr-1"></i> Libro de Asistencia SENCE - <?php echo htmlspecialchars($selected_group_name); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="thead-light bg-light">
                        <tr>
                            <th>Participante</th>
                            <th>RUN Oficial</th>
                            <th>Grupo Moodle (ID Acción)</th>
                            <th>Estado Actual</th>
                            <th>Horas Efectivas</th>
                            <th>% Cumplimiento</th>
                            <th>Estado SENCE</th>
                            <?php if ($is_admin): ?>
                                <th class="text-center">Auditoría</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($display_users)): ?>
                            <tr><td colspan="<?php echo $is_admin ? 8 : 7; ?>" class="text-center p-4 text-muted">No existen participantes asignados a este Grupo en este curso.</td></tr>
                        <?php else: ?>
                            <?php foreach ($display_users as $user): 
                                $ua = sence_helper::get_user_action_config($courseid, $user->id);
                                $run = sence_helper::get_user_run($user);
                                $activesession = $DB->get_record('block_sence_sessions', ['courseid' => $courseid, 'userid' => $user->id, 'status' => 'ACTIVE']);
                                $total_sec = (int)$DB->get_field_sql(
                                    "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
                                    ['courseid' => $courseid, 'userid' => $user->id]
                                ) ?: 0;
                                $hours = round($total_sec / 3600, 1);
                                $req_hours = (float)$ua->horastotales;
                                $pct = ($req_hours > 0) ? min(100, round(($hours / $req_hours) * 100)) : 0;
                                $min_pct = (float)$ua->asistenciaminima;
                                $is_approved = ($pct >= $min_pct);

                                $user_sessions_url = new moodle_url('/blocks/sence_connector/report.php', [
                                    'courseid' => $courseid,
                                    'tab' => 'sessions',
                                    'userid' => $user->id,
                                    'groupid' => $filter_groupid
                                ]);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo fullname($user); ?></strong><br>
                                    <small class="text-muted"><?php echo $user->email; ?></small>
                                </td>
                                <td><code><?php echo $run ?: 'Sin RUN'; ?></code></td>
                                <td>
                                    <strong class="text-primary">
                                        <code><?php echo htmlspecialchars($ua->codigooferta); ?></code>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($activesession): ?>
                                        <span class="badge bg-success badge-success">Activo (ClaveÚnica)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary badge-secondary">Desconectado</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $hours; ?> hrs</strong></td>
                                <td>
                                    <div class="progress" style="height: 6px; width: 100px;">
                                        <div class="progress-bar bg-info" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $pct; ?>% (Min: <?php echo $min_pct; ?>%)</small>
                                </td>
                                <td>
                                    <?php if ($is_approved): ?>
                                        <span class="badge bg-success badge-success">APROBADO</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary badge-primary">CURSANDO</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($is_admin): ?>
                                    <td class="text-center">
                                        <a href="<?php echo $user_sessions_url->out(false); ?>" class="btn btn-outline-info btn-sm" title="Ver sesiones individuales">
                                            <i class="fa fa-search"></i> Ver Sesiones
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// TAB 2: BITACORA SIC (SOLO ADMIN)
if ($tab === 'synclog' && $is_admin) {
    $synclogs = $DB->get_records('block_sence_synclog', ['courseid' => $courseid], 'id DESC', '*', 0, 50);
    ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">
                <i class="fa fa-history me-1 mr-1"></i> Historial de Envíos al Gestor Intermedio SENCE (API SIC)
            </span>
            <small class="text-muted">Mostrando los últimos 50 envíos registrados</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="thead-light bg-light">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Código Estado HTTP</th>
                            <th>Resultado Transmisión</th>
                            <th>Detalle de Carga (Payload JSON)</th>
                            <th>Respuesta del Servidor SENCE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($synclogs)): ?>
                            <tr>
                                <td colspan="5" class="text-center p-4 text-muted">
                                    No se han registrado envíos de sincronización aún para este curso.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($synclogs as $log): ?>
                            <tr>
                                <td style="white-space: nowrap;">
                                    <strong><?php echo userdate($log->timecreated, '%d/%m/%Y'); ?></strong><br>
                                    <small class="text-muted"><?php echo userdate($log->timecreated, '%H:%M:%S'); ?></small>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($log->statuscode == 200) ? 'bg-success badge-success' : 'bg-danger badge-danger'; ?>">
                                        HTTP <?php echo $log->statuscode; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log->success): ?>
                                        <span class="text-success font-weight-bold"><i class="fa fa-check-circle"></i> Sincronizado OK</span>
                                    <?php else: ?>
                                        <span class="text-danger font-weight-bold"><i class="fa fa-times-circle"></i> Fallido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#payload-<?php echo $log->id; ?>" aria-expanded="false">
                                        <i class="fa fa-code"></i> Ver JSON Enviado
                                    </button>
                                    <div class="collapse mt-2" id="payload-<?php echo $log->id; ?>">
                                        <pre class="bg-dark text-light p-2 rounded small" style="max-height: 250px; overflow-y: auto;"><code><?php echo htmlspecialchars($log->payload); ?></code></pre>
                                    </div>
                                </td>
                                <td>
                                    <pre class="bg-light p-2 rounded small m-0" style="max-height: 120px; overflow-y: auto;"><code><?php echo htmlspecialchars($log->response ?: 'Sin respuesta'); ?></code></pre>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// TAB 3: REGISTRO FORENSE DE SESIONES RCE (SOLO ADMIN)
if ($tab === 'sessions' && $is_admin) {
    $params = ['courseid' => $courseid];
    $sql_where = "courseid = :courseid";
    if (!empty($filter_user)) {
        $sql_where .= " AND userid = :userid";
        $params['userid'] = $filter_user;
    }
    $sessions = $DB->get_records_select('block_sence_sessions', $sql_where, $params, 'id DESC', '*', 0, 100);
    ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">
                <i class="fa fa-shield me-1 mr-1"></i> Trazabilidad Forense de Asistencia RCE (ClaveÚnica)
            </span>
            <div>
                <?php if (!empty($filter_user)): 
                    $u_filtered = $DB->get_record('user', ['id' => $filter_user]);
                ?>
                    <span class="badge bg-info badge-info me-2 mr-2">Filtrado por: <?php echo fullname($u_filtered); ?></span>
                    <a href="<?php echo (new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'tab' => 'sessions', 'groupid' => $filter_groupid]))->out(false); ?>" class="btn btn-sm btn-outline-secondary">
                        Ver Todos
                    </a>
                <?php else: ?>
                    <small class="text-muted">Últimas 100 sesiones registradas</small>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="thead-light bg-light">
                        <tr>
                            <th>ID Sesión Moodle</th>
                            <th>Participante</th>
                            <th>RUN Alumno</th>
                            <th>Inicio Sesión</th>
                            <th>Cierre Sesión</th>
                            <th>Duración Efectiva</th>
                            <th>IP Conexión</th>
                            <th>ID Sesión SENCE</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr><td colspan="9" class="text-center p-4 text-muted">No existen sesiones de asistencia registradas para los criterios seleccionados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $s): 
                                $s_user = $DB->get_record('user', ['id' => $s->userid]);
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($s->idsesionalumno); ?></code></td>
                                <td><strong><?php echo $s_user ? fullname($s_user) : 'Usuario ID ' . $s->userid; ?></strong></td>
                                <td><code><?php echo htmlspecialchars($s->runalumno); ?></code></td>
                                <td><?php echo userdate($s->timestart, '%d/%m/%Y %H:%M:%S'); ?></td>
                                <td><?php echo $s->timeend ? userdate($s->timeend, '%d/%m/%Y %H:%M:%S') : '<span class="text-success font-weight-bold"><i class="fa fa-circle"></i> En Curso</span>'; ?></td>
                                <td>
                                    <?php 
                                        $mins = round($s->duration / 60, 1);
                                        echo "<strong>{$mins} min</strong> (" . round($s->duration / 3600, 2) . " hrs)";
                                    ?>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($s->ipaddress ?: 'N/A'); ?></small></td>
                                <td><small><code><?php echo htmlspecialchars($s->idsesionsence ?: 'Pendiente'); ?></code></small></td>
                                <td>
                                    <?php if ($s->status === 'ACTIVE'): ?>
                                        <span class="badge bg-success badge-success">ACTIVA</span>
                                    <?php elseif ($s->status === 'CLOSED'): ?>
                                        <span class="badge bg-secondary badge-secondary">CERRADA</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning badge-warning"><?php echo htmlspecialchars($s->status); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// Footer institucional Keystone Advisors
?>
<div class="text-center my-4 py-3 text-muted small border-top">
    Desarrollado y respaldado de forma oficial y gratuita por<br>
    <a href="https://www.keystoneadvisors.cl" target="_blank" rel="noopener" class="font-weight-bold text-decoration-none" style="color: #225268;">
        Keystone Advisors
    </a> | Soluciones Tecnológicas de Cumplimiento Normativo SENCE | Av. Apoquindo 6410, Of. 605, Las Condes.
</div>
<?php

echo $OUTPUT->footer();
