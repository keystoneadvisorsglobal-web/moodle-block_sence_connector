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
 * Cierre local ágil y seguro de asistencia SENCE sin expulsar al alumno del curso.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
require_sesskey();

global $DB, $USER;

// Buscar sesión SENCE activa
$activesession = $DB->get_record('block_sence_sessions', [
    'courseid' => $courseid,
    'userid' => $USER->id,
    'status' => 'ACTIVE'
]);

if ($activesession) {
    $now = time();
    $activesession->status = 'CLOSED';
    $activesession->timeend = $now;
    if (empty($activesession->duration) || $activesession->duration <= 0) {
        $activesession->duration = max(0, $now - (int)$activesession->timestart);
    }
    $DB->update_record('block_sence_sessions', $activesession);

    $hours = round($activesession->duration / 3600, 1);
    \core\notification::info("⏸️ Sesión de asistencia SENCE pausada correctamente. Tu tiempo efectivo ha quedado registrado en tu historial.");
}

redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
