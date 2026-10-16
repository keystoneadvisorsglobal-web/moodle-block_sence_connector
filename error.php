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
 * Endpoint Callback for SENCE RCE UrlError.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

use block_sence_connector\util\sence_helper;

$glosaerror = optional_param('GlosaError', 0, PARAM_INT);
$idsesionalumno = optional_param('IdSesionAlumno', '', PARAM_TEXT);
$codigocurso = optional_param('CodigoCurso', '', PARAM_TEXT);

$err_msg = sence_helper::translate_error_code($glosaerror);

\core\notification::error("Error en validación SENCE: " . $err_msg);

$course = $DB->get_record('block_sence_courses', ['codigooferta' => $codigocurso]);
$redirect_url = $course ? new moodle_url('/course/view.php', ['id' => $course->courseid]) : new moodle_url('/my');

redirect($redirect_url);
