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

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/sence_connector:manage', $context);

$PAGE->set_url(new moodle_url('/blocks/sence_connector/course_settings.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('course_settings_title', 'block_sence_connector'));
$PAGE->set_heading($course->fullname);

$form = new \block_sence_connector\form\course_settings_form(null, ['courseid' => $courseid]);

// Load existing data
$current = $DB->get_record('block_sence_courses', ['courseid' => $courseid]);
if ($current) {
    $form->set_data($current);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else if ($data = $form->get_data()) {
    $record = (object)[
        'courseid' => $courseid,
        'enabled' => $data->enabled ? 1 : 0,
        'codsence' => trim($data->codsence ?? ''),
        'codigooferta' => trim($data->codigooferta ?? ''),
        'codigogrupo' => trim($data->codigogrupo ?? '1'),
        'lineacapacitacion' => (int)($data->lineacapacitacion ?? 3),
        'horastotales' => (float)($data->horastotales ?? 20.0),
        'asistenciaminima' => (float)($data->asistenciaminima ?? 75.0),
        'notaminima' => (float)($data->notaminima ?? 4.0),
        'timemodified' => time(),
    ];

    if ($current) {
        $record->id = $current->id;
        $DB->update_record('block_sence_courses', $record);
    } else {
        $record->timecreated = time();
        $DB->insert_record('block_sence_courses', $record);
    }

    \core\notification::success(get_string('changessaved'));
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_settings_title', 'block_sence_connector'));
$form->display();
echo $OUTPUT->footer();
