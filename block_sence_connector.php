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
 * Main block class for block_sence_connector.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_sence_connector\util\sence_helper;

class block_sence_connector extends block_base {

    public function init() {
        $this->title = get_string('block_title', 'block_sence_connector');
    }

    public function applicable_formats() {
        return [
            'course-view' => true,
            'site' => true,
            'my' => true,
        ];
    }

    public function instance_allow_multiple() {
        return true; // Allows block to be added to multiple regions or blocks on same page
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $USER, $DB, $COURSE, $OUTPUT, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $courseid = $this->page->course->id;
        $context = context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            $context = context_system::instance();
        }

        $isteacher = has_capability('block/sence_connector:viewreports', $context);
        $scourse = $DB->get_record('block_sence_courses', ['courseid' => $courseid]);

        if (!$scourse || !$scourse->enabled) {
            if ($isteacher) {
                $configure_url = new moodle_url('/blocks/sence_connector/course_settings.php', ['courseid' => $courseid]);
                $this->content->text = html_writer::tag('div',
                    html_writer::tag('p', 'Este curso no tiene habilitada la sincronización SENCE.', ['class' => 'text-muted small']) .
                    html_writer::link($configure_url, 'Configurar SENCE para este curso', ['class' => 'btn btn-sm btn-outline-secondary w-100']),
                    ['class' => 'p-2 text-center']
                );
            }
            return $this->content;
        }

        $rutotec = get_config('block_sence_connector', 'rutotec');
        $token = get_config('block_sence_connector', 'token');
        $run = sence_helper::get_user_run($USER);

        // Check active session for this user
        $activesession = $DB->get_record('block_sence_sessions', [
            'courseid' => $courseid,
            'userid' => $USER->id,
            'status' => 'ACTIVE'
        ]);

        // Compute total seconds
        $total_seconds = (int)$DB->get_field_sql(
            "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
            ['courseid' => $courseid, 'userid' => $USER->id]
        ) ?: 0;
        $total_hours = round($total_seconds / 3600, 1);
        $required_hours = (float)$scourse->horastotales;
        $attendance_pct = ($required_hours > 0) ? min(100, round(($total_hours / $required_hours) * 100)) : 0;

        $template_data = [
            'is_teacher' => $isteacher,
            'is_active' => !empty($activesession),
            'run_student' => $run,
            'hours_connected' => $total_hours,
            'hours_required' => $required_hours,
            'attendance_percent' => $attendance_pct,
            'rce_iniciar_url' => sence_helper::get_rce_base_url() . 'IniciarSesion',
            'rce_cerrar_url' => sence_helper::get_rce_base_url() . 'CerrarSesion',
            'rut_otec' => $rutotec,
            'token' => $token,
            'cod_sence' => $scourse->codsence ?: '-1',
            'codigo_curso' => $scourse->codigooferta ?: "CURSO-{$courseid}",
            'linea_capacitacion' => $scourse->lineacapacitacion,
            'id_sesion_alumno' => !empty($activesession) ? $activesession->idsesionalumno : 'SES-' . $USER->id . '-' . time(),
            'id_sesion_sence' => !empty($activesession) ? $activesession->idsesionsence : '',
            'url_retoma' => $CFG->wwwroot . '/blocks/sence_connector/retoma.php',
            'url_error' => $CFG->wwwroot . '/blocks/sence_connector/error.php',
            'report_url' => (new moodle_url('/blocks/sence_connector/report.php', ['courseid' => $courseid]))->out(false),
            'settings_url' => (new moodle_url('/blocks/sence_connector/course_settings.php', ['courseid' => $courseid]))->out(false),
        ];

        $this->content->text = $OUTPUT->render_from_template('block_sence_connector/student_view', $template_data);

        return $this->content;
    }
}
