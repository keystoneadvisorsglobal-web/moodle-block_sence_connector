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
 * Endpoint Callback for SENCE RCE UrlRetoma (Successful login/logout).
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $DB, $USER;

$codsence = optional_param('CodSence', '', PARAM_TEXT);
$codigocurso = optional_param('CodigoCurso', '', PARAM_TEXT);
$idsesionalumno = required_param('IdSesionAlumno', PARAM_TEXT);
$idsesionsence = optional_param('IdSesionSence', '', PARAM_TEXT);
$runalumno = optional_param('RunAlumno', '', PARAM_TEXT);
$fechahora = optional_param('FechaHora', '', PARAM_TEXT);

// Match session or create active session
$session = $DB->get_record('block_sence_sessions', ['idsesionalumno' => $idsesionalumno]);

$courseid = SITEID;
if ($codigocurso) {
    $course = $DB->get_record('block_sence_courses', ['codigooferta' => $codigocurso]);
    if ($course) {
        $courseid = (int)$course->courseid;
    }
}

if (!$session) {
    // New login session
    $userid = $USER->id ?? 0;
    if (!$userid && !empty($runalumno)) {
        $u = $DB->get_record('user', ['idnumber' => $runalumno]);
        if ($u) {
            $userid = $u->id;
            // Restore user session if lost during cross-site post
            \core\session\manager::set_user($u);
        }
    }

    $record = (object)[
        'courseid' => $courseid,
        'userid' => $userid,
        'runalumno' => $runalumno ?: ($USER->idnumber ?? ''),
        'idsesionalumno' => $idsesionalumno,
        'idsesionsence' => $idsesionsence,
        'status' => 'ACTIVE',
        'timestart' => time(),
        'timeend' => 0,
        'duration' => 0,
        'ipaddress' => getremoteaddr(),
        'glosaerror' => '',
    ];
    $DB->insert_record('block_sence_sessions', $record);

    \core\notification::success("Asistencia SENCE registrada correctamente mediante ClaveÚnica.");
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else {
    // Session closure callback
    $session->status = 'CLOSED';
    $session->timeend = time();
    $session->duration = max(0, $session->timeend - $session->timestart);
    $DB->update_record('block_sence_sessions', $session);

    \core\notification::info("Sesión de asistencia SENCE finalizada correctamente.");
    redirect(new moodle_url('/course/view.php', ['id' => $session->courseid]));
}
