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

namespace block_sence_connector\observer;

defined('MOODLE_INTERNAL') || die();

/**
 * Global course and module navigation observer to enforce SENCE ClaveÚnica validation.
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_observer {

    /**
     * Intercept course module views (activities, quizzes, resources).
     *
     * @param \core\event\course_module_viewed $event
     */
    public static function course_module_viewed(\core\event\course_module_viewed $event) {
        self::check_sence_access($event->courseid);
    }

    /**
     * Intercept course main page view.
     *
     * @param \core\event\course_viewed $event
     */
    public static function course_viewed(\core\event\course_viewed $event) {
        self::check_sence_access($event->courseid);
    }

    /**
     * Intercept grade reports, user profile in course, activities list, etc.
     *
     * @param \core\event\base $event
     */
    public static function course_generic_event(\core\event\base $event) {
        if (!empty($event->courseid)) {
            self::check_sence_access((int)$event->courseid);
        }
    }

    /**
     * Verify SENCE active session and redirect to gatekeeper page if inactive.
     * Automatically called across all course sub-pages, tabs, grades, and modules.
     *
     * @param int $courseid
     */
    public static function check_sence_access(int $courseid) {
        global $USER, $DB;

        if ($courseid <= 1) {
            return;
        }

        if (!isloggedin() || isguestuser() || empty($USER->id)) {
            return;
        }

        // Avoid infinite loop if already on validation endpoints
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/blocks/sence_connector/gatekeeper.php') !== false ||
            strpos($uri, '/blocks/sence_connector/retoma.php') !== false ||
            strpos($uri, '/blocks/sence_connector/error.php') !== false) {
            return;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return;
        }

        // Teachers, managers and site admins are never blocked
        if (has_capability('block/sence_connector:viewreports', $context) || is_siteadmin()) {
            return;
        }

        // Check if course has SENCE enabled
        $scourse = $DB->get_record('block_sence_courses', ['courseid' => $courseid, 'enabled' => 1], '*', IGNORE_MULTIPLE);
        if (!$scourse) {
            return;
        }

        // Check if student has an active SENCE attendance session
        $activesession = $DB->get_record('block_sence_sessions', [
            'courseid' => $courseid,
            'userid' => $USER->id,
            'status' => 'ACTIVE'
        ]);

        if (!$activesession) {
            $gatekeeper_url = new \moodle_url('/blocks/sence_connector/gatekeeper.php', ['courseid' => $courseid]);
            redirect($gatekeeper_url);
        }
    }
}
