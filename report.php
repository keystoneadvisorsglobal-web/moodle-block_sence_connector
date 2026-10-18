<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    block_sence_connector
// @copyright  2026 Keystone Advisors SpA (https://www.keystoneadvisors.cl)
// @license    Proprietary / Keystone Advisors Freeware License (All Rights Reserved)
//
// Queda prohibida la reproducción, modificación, copia o comercialización total
// o parcial de este software sin el consentimiento expreso y por escrito de Keystone Advisors.

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
    $filename = 'Liquidacion_SENCE_' . clean_param($course->shortname, PARAM_ALPHANUMEXT) . '_Grupo_' . clean_param($selected_group_name, PARAM_ALPHANUMEXT) . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    fputcsv($output, [
        'ID Curso',
        'Nombre Curso Moodle',
        'RUT Alumno',
        'Nombre Completo',
        'Email',
        'Grupo Moodle / ID Accion SENCE',
        'Codigo SENCE',
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
            $course->id,
            $course->fullname,
            $run ?: 'Sin RUN',
            fullname($u),
            $u->email,
            $ua->codigooferta,
            $ua->codsence ?: '-1',
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

$export_csv_url = new moodle_url('/blocks/sence_connector/report.php', [
    'courseid' => $courseid,
    'action' => 'export_csv',
    'groupid' => $filter_groupid
]);
?>

<div class="sence-report-hub mb-4">
    <!-- Header Institucional -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px; background: linear-gradient(135deg, #1a4253 0%, #0d2833 100%);">
        <div class="card-body p-4 text-white d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-success badge-success text-white me-2 mr-2" style="font-size: 0.75rem;">Módulo Oficial de Fiscalización</span>
                    <span class="badge bg-info badge-info text-white" style="font-size: 0.75rem;">Keystone Advisors</span>
                </div>
                <h2 class="h3 font-weight-bold fw-bold mb-1" style="color: #ffffff;">
                    <i class="fa fa-university me-2 mr-2"></i> Centro de Informes y Auditoría SENCE
                </h2>
                <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
                    Curso: <strong><?php echo htmlspecialchars($course->fullname); ?></strong> (ID: <code><?php echo $course->id; ?></code> | Código Corto: <code><?php echo htmlspecialchars($course->shortname); ?></code>)
                </p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?php echo $export_csv_url->out(false); ?>" class="btn btn-success font-weight-bold shadow-sm me-2 mr-2">
                    <i class="fa fa-file-excel-o me-1 mr-1"></i> Descargar Liquidación OTIC (CSV)
                </a>
                <?php if ($is_admin): ?>
                    <a href="<?php echo $sync_url->out(false); ?>" class="btn btn-warning font-weight-bold shadow-sm" onclick="return confirm('¿Deseas forzar el envío del avance de este grupo al SIC Gestor Intermedio de SENCE?');">
                        <i class="fa fa-cloud-upload me-1 mr-1"></i> Sincronizar SIC Ahora
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filtro Dinámico por Grupo Moodle / ID de Acción SENCE -->
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white" style="border-radius: 10px;">
        <form method="GET" action="<?php echo $PAGE->url->out_omit_querystring(); ?>" class="row align-items-center">
            <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <div class="col-md-5">
                <label class="form-label font-weight-bold fw-bold text-dark mb-1">
                    <i class="fa fa-filter me-1 mr-1 text-primary"></i> Filtrar por Grupo / Empresa / ID de Acción SENCE:
                </label>
                <select name="groupid" class="form-select form-control" onchange="this.form.submit()">
                    <option value="0" <?php echo ($filter_groupid == 0) ? 'selected' : ''; ?>>-- Ver Todos los Grupos / Acciones del Curso --</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?php echo $g->id; ?>" <?php echo ($filter_groupid == $g->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g->name); ?> (<?php echo count(groups_get_members($g->id)); ?> alumnos)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7 text-md-end text-end mt-3 mt-md-0">
                <span class="text-muted small me-2 mr-2">
                    Visualizando: <strong><?php echo htmlspecialchars($selected_group_name); ?></strong> (<?php echo count($display_users); ?> alumnos)
                </span>
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
            <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa fa-list me-1 mr-1"></i> Libro de Asistencia SENCE - <?php echo htmlspecialchars($selected_group_name); ?>
                </span>
                <span class="badge bg-primary text-white" style="font-size: 0.78rem;">
                    Curso: <?php echo htmlspecialchars($course->fullname); ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0" style="font-size: 0.82rem;">
                        <thead class="thead-light bg-light" style="font-size: 0.78rem;">
                            <tr>
                                <th style="white-space: nowrap;">Participante</th>
                                <th style="white-space: nowrap;">Curso</th>
                                <th style="white-space: nowrap;">RUN</th>
                                <th style="white-space: nowrap;">Grupo (Acción)</th>
                                <th style="white-space: nowrap;">Estado</th>
                                <th style="white-space: nowrap;">Horas Efectivas</th>
                                <th style="min-width: 140px;">% Cumplimiento</th>
                                <th style="white-space: nowrap;">SENCE</th>
                                <?php if ($is_admin): ?>
                                    <th class="text-center" style="white-space: nowrap;">Auditoría</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($display_users)): ?>
                                <tr><td colspan="<?php echo $is_admin ? 9 : 8; ?>" class="text-center p-4 text-muted">No existen participantes asignados a este Grupo en este curso.</td></tr>
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
                                    <td style="line-height: 1.2;">
                                        <strong style="color: #1a4253;"><?php echo fullname($user); ?></strong><br>
                                        <small class="text-muted" style="font-size: 0.72rem;"><?php echo $user->email; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                            <?php echo htmlspecialchars($course->shortname); ?>
                                        </span>
                                    </td>
                                    <td><code style="font-size: 0.76rem;"><?php echo $run ?: 'Sin RUN'; ?></code></td>
                                    <td>
                                        <strong class="text-primary">
                                            <code style="font-size: 0.76rem;"><?php echo htmlspecialchars($ua->codigooferta); ?></code>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($activesession): ?>
                                            <span class="badge bg-success badge-success" style="font-size: 0.70rem;">Activo (RCE)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary badge-secondary" style="font-size: 0.70rem;">Desconectado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong style="font-size: 0.82rem;"><?php echo $hours; ?> hrs</strong></td>
                                    <td>
                                        <div class="progress" style="height: 12px; margin-bottom: 2px;">
                                            <div class="progress-bar <?php echo $is_approved ? 'bg-success' : 'bg-info'; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $pct; ?>%; font-size: 0.65rem; line-height: 12px;" 
                                                 aria-valuenow="<?php echo $pct; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo $pct; ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.68rem;"><?php echo $hours; ?>/<?php echo $req_hours; ?>h req.</small>
                                    </td>
                                    <td>
                                        <?php if ($is_approved): ?>
                                            <span class="badge bg-success badge-success" style="font-size: 0.70rem;"><i class="fa fa-check"></i> Cumple SENCE</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning badge-warning text-dark" style="font-size: 0.70rem;"><i class="fa fa-clock-o"></i> En Curso (&lt;<?php echo $min_pct; ?>%)</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($is_admin): ?>
                                        <td class="text-center">
                                            <a href="<?php echo $user_sessions_url->out(false); ?>" class="btn btn-xs btn-sm btn-outline-info py-0 px-2" style="font-size: 0.72rem;" title="Ver sesiones individuales">
                                                <i class="fa fa-history"></i> Sesiones
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

    // TAB 2: BITÁCORA SIC GESTOR INTERMEDIO
    else if ($tab === 'synclog' && $is_admin) {
        $logs = $DB->get_records('block_sence_synclog', ['courseid' => $courseid], 'id DESC', '*', 0, 50);
        ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white font-weight-bold">
                <i class="fa fa-exchange me-1 mr-1"></i> Envíos Automáticos a la API Gestor Intermedio SENCE (SIC)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle m-0" style="font-size: 0.80rem;">
                        <thead class="table-dark" style="font-size: 0.76rem;">
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Estado HTTP</th>
                                <th>Resultado</th>
                                <th>Carga Útil Enviada (JSON)</th>
                                <th>Respuesta SENCE (SIC)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="5" class="text-center p-4 text-muted">Aún no se han ejecutado sincronizaciones automáticas para este curso.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><strong><?php echo userdate($log->timecreated, '%d/%m/%Y %H:%M:%S'); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo $log->statuscode ?: 'N/A'; ?></span></td>
                                        <td>
                                            <?php if ($log->success): ?>
                                                <span class="badge bg-success badge-success"><i class="fa fa-check"></i> Exitoso</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger badge-danger"><i class="fa fa-times"></i> Error</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <details>
                                                <summary class="text-primary" style="cursor: pointer;">Ver JSON enviado</summary>
                                                <pre class="bg-light p-2 mt-2 rounded border" style="max-height: 150px; overflow-y: auto; font-size: 0.72rem;"><?php echo htmlspecialchars($log->payload); ?></pre>
                                            </details>
                                        </td>
                                        <td>
                                            <details>
                                                <summary class="text-info" style="cursor: pointer;">Ver Respuesta SENCE</summary>
                                                <pre class="bg-light p-2 mt-2 rounded border" style="max-height: 150px; overflow-y: auto; font-size: 0.72rem;"><?php echo htmlspecialchars($log->response); ?></pre>
                                            </details>
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

    // TAB 3: REGISTRO FORENSE DE SESIONES RCE
    else if ($tab === 'sessions' && $is_admin) {
        $filter_user = optional_param('userid', 0, PARAM_INT);
        $params = ['courseid' => $courseid];
        $where = 'courseid = :courseid';
        if ($filter_user > 0) {
            $params['userid'] = $filter_user;
            $where .= ' AND userid = :userid';
        }
        $sessions = $DB->get_records_select('block_sence_sessions', $where, $params, 'id DESC', '*', 0, 100);
        ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa fa-shield me-1 mr-1"></i> Auditoría Forense de Conexiones SENCE RCE
                    <?php if ($filter_user > 0): 
                        $u = $DB->get_record('user', ['id' => $filter_user]);
                    ?>
                        (Filtrado por: <strong><?php echo fullname($u); ?></strong>)
                    <?php endif; ?>
                </span>
                <?php if ($filter_user > 0): ?>
                    <a href="<?php echo (new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid, 'tab' => 'sessions', 'groupid' => $filter_groupid]))->out(false); ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-times me-1 mr-1"></i> Quitar Filtro de Alumno
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle m-0" style="font-size: 0.80rem;">
                        <thead class="table-dark" style="font-size: 0.76rem;">
                            <tr>
                                <th>Curso</th>
                                <th>Participante</th>
                                <th>RUN</th>
                                <th>ID Sesión Alumno</th>
                                <th>ID Sesión SENCE</th>
                                <th>Inicio</th>
                                <th>Término</th>
                                <th>Duración Efectiva</th>
                                <th>IP Auditoría</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr><td colspan="10" class="text-center p-4 text-muted">No existen registros forenses de sesiones para este criterio.</td></tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $s): 
                                    $u = $DB->get_record('user', ['id' => $s->userid]);
                                    $dur_mins = round((int)$s->duration / 60, 1);
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark border" style="font-size: 0.70rem;">
                                                <?php echo htmlspecialchars($course->shortname); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo $u ? fullname($u) : "ID {$s->userid}"; ?></strong></td>
                                        <td><code style="font-size: 0.74rem;"><?php echo htmlspecialchars($s->runalumno); ?></code></td>
                                        <td><code style="font-size: 0.72rem;"><?php echo htmlspecialchars($s->idsesionalumno); ?></code></td>
                                        <td><code style="font-size: 0.72rem;"><?php echo htmlspecialchars($s->idsesionsence ?: 'Pendiente'); ?></code></td>
                                        <td><?php echo $s->timestart ? userdate($s->timestart, '%d/%m/%Y %H:%M:%S') : '-'; ?></td>
                                        <td><?php echo $s->timeend ? userdate($s->timeend, '%d/%m/%Y %H:%M:%S') : '<span class="badge bg-success" style="font-size: 0.68rem;">En Curso</span>'; ?></td>
                                        <td><strong><?php echo $dur_mins; ?> min</strong></td>
                                        <td><code><?php echo htmlspecialchars($s->ipaddress ?: '-'); ?></code></td>
                                        <td>
                                            <?php if ($s->status === 'ACTIVE'): ?>
                                                <span class="badge bg-success" style="font-size: 0.70rem;">ACTIVA</span>
                                            <?php elseif ($s->status === 'CLOSED'): ?>
                                                <span class="badge bg-secondary" style="font-size: 0.70rem;">CERRADA</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" style="font-size: 0.70rem;"><?php echo htmlspecialchars($s->status); ?></span>
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
    ?>
</div>

<?php
echo $OUTPUT->footer();
