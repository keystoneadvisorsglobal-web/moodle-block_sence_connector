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

require_once(__DIR__ . '/../../config.php');

use block_sence_connector\util\sence_helper;
use block_sence_connector\task\sync_sic_task;

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/sence_connector:viewreports', $context);

$scourse = $DB->get_record('block_sence_courses', ['courseid' => $courseid], '*', MUST_EXIST);

// Manual Sync trigger
if ($action === 'sync' && confirm_sesskey()) {
    $rutotec = get_config('block_sence_connector', 'rutotec');
    $token = get_config('block_sence_connector', 'token');
    $idsistema = (int)(get_config('block_sence_connector', 'idsistema') ?: 1350);

    sync_sic_task::sync_course($scourse, $rutotec, $token, $idsistema);
    \core\notification::success("Sincronización manual con API Gestor Intermedio (SIC) ejecutada exitosamente.");
    redirect(new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid]));
}

$PAGE->set_url(new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('report_title', 'block_sence_connector'));
$PAGE->set_heading($course->fullname . ' - Libro de Asistencia SENCE');

echo $OUTPUT->header();

// Action Toolbar
$sync_url = new moodle_url('/blocks/sence_connector/report.php', [
    'courseid' => $courseid,
    'action' => 'sync',
    'sesskey' => sesskey()
]);
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-4');
echo html_writer::tag('h3', 'Libro de Asistencia y Avance SENCE', ['class' => 'm-0']);
echo html_writer::link($sync_url, '<i class="fa fa-refresh me-1 mr-1"></i> Sincronizar Ahora con SIC', ['class' => 'btn btn-primary']);
echo html_writer::end_div();

// Enrolled students
$enrolled = get_enrolled_users($context, 'block/sence_connector:view');

// Stats Cards
$total_enrolled = count($enrolled);
$today_start = strtotime('today midnight');
$active_today = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT userid) FROM {block_sence_sessions} WHERE courseid = :courseid AND timestart >= :today",
    ['courseid' => $courseid, 'today' => $today_start]
);

// Last sync
$last_sync = $DB->get_record_sql(
    "SELECT * FROM {block_sence_synclog} WHERE courseid = :courseid ORDER BY id DESC",
    ['courseid' => $courseid],
    IGNORE_MULTIPLE
);

?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 p-3 bg-light">
            <span class="text-muted small">Alumnos Inscritos</span>
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
            <span class="text-muted small">Horas Exigidas</span>
            <h3 class="font-weight-bold text-info m-0"><?php echo $scourse->horastotales; ?> hrs</h3>
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

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="thead-light bg-light">
                    <tr>
                        <th>Participante</th>
                        <th>RUN / RUT</th>
                        <th>Estado Sesión</th>
                        <th>Horas Efectivas</th>
                        <th>% Asistencia</th>
                        <th>Estado SENCE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrolled)): ?>
                        <tr><td colspan="6" class="text-center p-4 text-muted">No hay estudiantes matriculados en este curso.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrolled as $user): 
                            $run = sence_helper::get_user_run($user);
                            $activesession = $DB->get_record('block_sence_sessions', ['courseid' => $courseid, 'userid' => $user->id, 'status' => 'ACTIVE']);
                            $total_sec = (int)$DB->get_field_sql(
                                "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
                                ['courseid' => $courseid, 'userid' => $user->id]
                            ) ?: 0;
                            $hours = round($total_sec / 3600, 1);
                            $req_hours = (float)$scourse->horastotales;
                            $pct = ($req_hours > 0) ? min(100, round(($hours / $req_hours) * 100)) : 0;
                            $is_approved = ($pct >= (float)$scourse->asistenciaminima);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo fullname($user); ?></strong><br>
                                <small class="text-muted"><?php echo $user->email; ?></small>
                            </td>
                            <td><code><?php echo $run ?: 'Sin RUT'; ?></code></td>
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
                                <small class="text-muted"><?php echo $pct; ?>%</small>
                            </td>
                            <td>
                                <?php if ($is_approved): ?>
                                    <span class="badge bg-success badge-success">Aprobado</span>
                                <?php else: ?>
                                    <span class="badge bg-primary badge-primary">Cursando</span>
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

echo $OUTPUT->footer();
