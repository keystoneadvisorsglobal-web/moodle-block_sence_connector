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
 * QA & Simulation Sandbox: Simulates SENCE ClaveUnica Callback for Testing & Demos.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use block_sence_connector\util\sence_helper;

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'start', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);

$environment = get_config('block_sence_connector', 'environment');
$allow_simulation = (bool)get_config('block_sence_connector', 'enable_simulation_mode');
$context = context_course::instance($courseid);
$isadmin = has_capability('moodle/site:config', context_system::instance()) || has_capability('block/sence_connector:viewreports', $context);

// Safety guard: only allowed in test environment OR if explicitly enabled in admin settings
if ($environment === 'prod' && !$allow_simulation && !$isadmin) {
    throw new moodle_exception('La simulación de asistencia solo está permitida en entorno de pruebas (test).');
}

$scourse = sence_helper::get_user_action_config($courseid, $USER->id);
$run = sence_helper::get_user_run($USER);

if ($action === 'start') {
    // Check if there is already an active session
    $active = $DB->get_record('block_sence_sessions', [
        'courseid' => $courseid,
        'userid' => $USER->id,
        'status' => 'ACTIVE'
    ]);

    if (!$active) {
        $sim_session_id = 'SIM-SES-' . $USER->id . '-' . time();
        $sim_sence_id = 'SENCE-SIM-' . rand(100000, 999999);

        $record = (object)[
            'courseid' => $courseid,
            'userid' => $USER->id,
            'runalumno' => $run ?: ($USER->idnumber ?? '11111111-1'),
            'idsesionalumno' => $sim_session_id,
            'idsesionsence' => $sim_sence_id,
            'status' => 'ACTIVE',
            'timestart' => time(),
            'timeend' => 0,
            'duration' => 0,
            'ipaddress' => getremoteaddr(),
            'glosaerror' => 'SIMULACION_QA_TEST',
        ];
        $DB->insert_record('block_sence_sessions', $record);

        \core\notification::success("🧪 Modo Simulación: Has iniciado sesión SENCE exitosamente como alumno. Todas las funciones del curso y cronómetro están activas.");
    } else {
        \core\notification::info("Ya tienes una sesión SENCE activa en este curso.");
    }

    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));

} elseif ($action === 'stop') {
    $active = $DB->get_record('block_sence_sessions', [
        'courseid' => $courseid,
        'userid' => $USER->id,
        'status' => 'ACTIVE'
    ]);

    if ($active) {
        $active->status = 'CLOSED';
        $active->timeend = time();
        $active->duration = max(0, $active->timeend - $active->timestart);
        $DB->update_record('block_sence_sessions', $active);

        \core\notification::info("🧪 Modo Simulación: Has cerrado tu asistencia SENCE. La sesión ha sido computada en tus horas totales.");
    }

    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));

} elseif ($action === 'add_hours') {
    // Add 2 hours for demonstration/testing of progress bar and audit report
    $sim_session_id = 'SIM-HIST-' . $USER->id . '-' . time();
    $two_hours = 7200;
    $record = (object)[
        'courseid' => $courseid,
        'userid' => $USER->id,
        'runalumno' => $run ?: ($USER->idnumber ?? '11111111-1'),
        'idsesionalumno' => $sim_session_id,
        'idsesionsence' => 'SENCE-SIM-HIST',
        'status' => 'CLOSED',
        'timestart' => time() - $two_hours,
        'timeend' => time(),
        'duration' => $two_hours,
        'ipaddress' => getremoteaddr(),
        'glosaerror' => 'SIMULACION_HORAS_ACUMULADAS',
    ];
    $DB->insert_record('block_sence_sessions', $record);

    \core\notification::success("🧪 Se han agregado +2 horas históricas de asistencia SENCE para probar el avance de la barra.");
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}
