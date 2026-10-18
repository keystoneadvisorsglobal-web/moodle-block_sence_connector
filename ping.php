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
 * Endpoint AJAX Heartbeat / Ping de Actividad Pedagógica Real SENCE.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json; charset=utf-8');

if (!isloggedin() || isguestuser()) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$courseid = required_param('courseid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);
$is_user_active = optional_param('active', 1, PARAM_INT); // 1 = interactuando, 0 = inactivo

if (!confirm_sesskey($sesskey)) {
    echo json_encode(['status' => 'error', 'message' => 'Token de sesión inválido']);
    exit;
}

global $DB, $USER;

// Buscar sesión SENCE activa para este usuario y curso
$activesession = $DB->get_record('block_sence_sessions', [
    'courseid' => $courseid,
    'userid' => $USER->id,
    'status' => 'ACTIVE'
]);

if (!$activesession) {
    echo json_encode([
        'status' => 'inactive',
        'has_active_session' => false,
        'message' => 'No hay sesión SENCE activa'
    ]);
    exit;
}

$now = time();
$timeout_minutes = (int)get_config('block_sence_connector', 'inactivity_timeout') ?: 15;
$timeout_seconds = $timeout_minutes * 60;

// Si la última actividad registrada superó el tiempo máximo de inactividad, cerramos por timeout
$last_active = !empty($activesession->lastping) ? (int)$activesession->lastping : (int)$activesession->timestart;
$time_since_last_ping = $now - $last_active;

if ($time_since_last_ping > $timeout_seconds && $activesession->duration > 0) {
    // Cerrar sesión por inactividad prolongada
    $activesession->status = 'CLOSED';
    $activesession->timeend = $last_active;
    $activesession->glosaerror = 'Cerrada automáticamente por inactividad pasiva prolongada (' . $timeout_minutes . ' min).';
    $DB->update_record('block_sence_sessions', $activesession);

    echo json_encode([
        'status' => 'timeout_closed',
        'has_active_session' => false,
        'message' => 'Sesión cerrada por inactividad'
    ]);
    exit;
}

// Si el usuario está interactuando activamente, sumamos el tiempo efectivo
if ($is_user_active) {
    // Intervalo de ping esperado es de 30 a 60 segundos
    $increment = min(60, max(0, $now - $last_active));
    if ($increment == 0) {
        $increment = 30; // Primer ping
    }
    $activesession->duration = (int)$activesession->duration + $increment;
    $activesession->lastping = $now;
    $DB->update_record('block_sence_sessions', $activesession);
}

// Calcular acumulado total
$closed_seconds = (int)$DB->get_field_sql(
    "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
    ['courseid' => $courseid, 'userid' => $USER->id]
) ?: 0;

$effective_total_seconds = $closed_seconds + (int)$activesession->duration;
$hours = floor($effective_total_seconds / 3600);
$minutes = floor(($effective_total_seconds % 3600) / 60);
$seconds = $effective_total_seconds % 60;

echo json_encode([
    'status' => 'success',
    'has_active_session' => true,
    'session_effective_seconds' => (int)$activesession->duration,
    'total_effective_seconds' => $effective_total_seconds,
    'formatted_effective_time' => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
    'is_user_active' => (bool)$is_user_active,
    'timeout_seconds' => $timeout_seconds
]);
