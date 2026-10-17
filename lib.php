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
 * Global lifecycle callbacks for block_sence_connector.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_sence_connector\observer\course_observer;

/**
 * Global hook executed before HTTP headers on EVERY Moodle page.
 * Intercepts any page within a SENCE-enabled course (grades, activities, participants, etc.)
 * and enforces mandatory SENCE ClaveÚnica authentication before rendering any content or menus.
 */
function block_sence_connector_before_http_headers() {
    global $PAGE, $COURSE;

    $courseid = 0;
    if (!empty($PAGE->course) && !empty($PAGE->course->id) && $PAGE->course->id > 1) {
        $courseid = (int)$PAGE->course->id;
    } else if (!empty($COURSE) && !empty($COURSE->id) && $COURSE->id > 1) {
        $courseid = (int)$COURSE->id;
    }

    if ($courseid > 1) {
        course_observer::check_sence_access($courseid);
    }
}

/**
 * Extend navigation hook: cleans up or restricts secondary navigation tabs for unvalidated students.
 *
 * @param global_navigation $navigation
 */
function block_sence_connector_extend_navigation(global_navigation $navigation) {
    global $PAGE, $COURSE;

    $courseid = 0;
    if (!empty($PAGE->course) && !empty($PAGE->course->id) && $PAGE->course->id > 1) {
        $courseid = (int)$PAGE->course->id;
    } else if (!empty($COURSE) && !empty($COURSE->id) && $COURSE->id > 1) {
        $courseid = (int)$COURSE->id;
    }

    if ($courseid > 1) {
        course_observer::check_sence_access($courseid);
    }
}
