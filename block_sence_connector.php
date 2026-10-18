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
 * Main block class for block_sence_connector with Multi-Company support and Student Live Dashboard.
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
        return true;
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
        $logo_url = $OUTPUT->image_url('logo_keystone', 'block_sence_connector')->out(false);

        // Resolve user-specific action config according to group
        $scourse = sence_helper::get_user_action_config($courseid, $USER->id);

        if (!$scourse || !$scourse->enabled) {
            if ($isteacher) {
                $configure_url = new moodle_url('/blocks/sence_connector/course_settings.php', ['courseid' => $courseid]);
                $this->content->text = html_writer::tag('div',
                    html_writer::empty_tag('img', ['src' => $logo_url, 'alt' => 'Keystone Advisors', 'style' => 'max-height: 38px; margin-bottom: 12px;']) .
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

        $session_start_ts = !empty($activesession) ? (int)$activesession->timestart : time();

        // Compute total seconds from closed sessions
        $closed_seconds = (int)$DB->get_field_sql(
            "SELECT SUM(duration) FROM {block_sence_sessions} WHERE courseid = :courseid AND userid = :userid AND status = 'CLOSED'",
            ['courseid' => $courseid, 'userid' => $USER->id]
        ) ?: 0;

        // Current session elapsed seconds
        $current_session_seconds = !empty($activesession) ? max(0, time() - $activesession->timestart) : 0;
        $total_seconds = $closed_seconds + $current_session_seconds;

        $total_hours = round($total_seconds / 3600, 1);
        $required_hours = (float)$scourse->horastotales;
        $attendance_pct = ($required_hours > 0) ? min(100, round(($total_hours / $required_hours) * 100)) : 0;
        $is_attendance_approved = ($attendance_pct >= 75);

        // Academic Progress (Course Completion)
        $course_record = $this->page->course;
        if (empty($course_record->id) || $course_record->id != $courseid) {
            $course_record = $DB->get_record('course', ['id' => $courseid]);
        }
        require_once($CFG->libdir . '/completionlib.php');
        $course_progress = 0;
        try {
            $cinfo = new completion_info($course_record);
            if ($cinfo->is_enabled()) {
                $raw_progress = \core_completion\progress::get_course_progress_percentage($course_record, $USER->id);
                $course_progress = ($raw_progress !== null) ? round($raw_progress) : 0;
            }
        } catch (\Throwable $e) {
            $course_progress = 0;
        }

        // Academic Grades (Course Grade)
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');
        $grade_display = 'N/A';
        $has_grade = false;
        $is_grade_passing = false;
        try {
            $gi = grade_item::fetch_course_item($courseid);
            if ($gi) {
                $gg = grade_grade::fetch(['itemid' => $gi->id, 'userid' => $USER->id]);
                if ($gg && !is_null($gg->finalgrade)) {
                    $grade_display = number_format((float)$gg->finalgrade, 1, ',', '.');
                    $has_grade = true;
                    if ($gi->gradepass > 0) {
                        $is_grade_passing = ((float)$gg->finalgrade >= (float)$gi->gradepass);
                    } else {
                        $is_grade_passing = ((float)$gg->finalgrade >= 4.0);
                    }
                }
            }
        } catch (\Throwable $e) {
            $grade_display = 'N/A';
        }

        // Get student's assigned group / action name
        $user_group_name = '';
        $usergroups = groups_get_all_groups($courseid, $USER->id);
        if (!empty($usergroups)) {
            $first_group = reset($usergroups);
            $user_group_name = $first_group->name;
        }

        $template_data = [
            'is_teacher' => $isteacher,
            'is_active' => !empty($activesession),
            'session_start_timestamp' => $session_start_ts,
            'user_fullname' => fullname($USER),
            'user_group_name' => $user_group_name,
            'run_student' => $run,
            'hours_connected' => $total_hours,
            'hours_required' => $required_hours,
            'attendance_percent' => $attendance_pct,
            'is_attendance_approved' => $is_attendance_approved,
            'course_progress' => $course_progress,
            'has_grade' => $has_grade,
            'grade_display' => $grade_display,
            'is_grade_passing' => $is_grade_passing,
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
            'upload_students_url' => (new moodle_url('/blocks/sence_connector/upload_students.php', ['courseid' => $courseid]))->out(false),
            'keystone_logo_url' => $logo_url,
        ];

        $this->content->text = $OUTPUT->render_from_template('block_sence_connector/student_view', $template_data);

        return $this->content;
    }
}
