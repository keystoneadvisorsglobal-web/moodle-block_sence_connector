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

namespace block_sence_connector\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy API provider for block_sence_connector.
 * Required for official Moodle Marketplace compliance (GDPR).
 *
 * @package    block_sence_connector
 * @copyright  2026 Keystone Advisors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the types of personal data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_sence_sessions',
            [
                'userid' => 'privacy:metadata:sessions:userid',
                'runalumno' => 'privacy:metadata:sessions:runalumno',
                'idsesionsence' => 'privacy:metadata:sessions:idsesionsence',
                'timestart' => 'privacy:metadata:sessions:timestart',
                'timeend' => 'privacy:metadata:sessions:timeend',
                'duration' => 'privacy:metadata:sessions:duration',
                'ipaddress' => 'privacy:metadata:sessions:ipaddress',
            ],
            'privacy:metadata:sessions'
        );

        $collection->add_external_location_link(
            'sence_rce',
            [
                'runalumno' => 'privacy:metadata:sessions:runalumno',
            ],
            'privacy:metadata:sessions'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course} co ON c.instanceid = co.id AND c.contextlevel = :contextlevel
                  JOIN {block_sence_sessions} s ON s.courseid = co.id
                 WHERE s.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }
            $sessions = $DB->get_records('block_sence_sessions', [
                'courseid' => $context->instanceid,
                'userid' => $userid
            ]);
            if (!empty($sessions)) {
                \core_privacy\local\request\writer::with_context($context)->export_data(
                    [get_string('pluginname', 'block_sence_connector')],
                    (object) ['sessions' => array_values($sessions)]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel == CONTEXT_COURSE) {
            $DB->delete_records('block_sence_sessions', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $DB->delete_records('block_sence_sessions', [
                    'courseid' => $context->instanceid,
                    'userid' => $userid
                ]);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_COURSE) {
            $sql = "SELECT userid FROM {block_sence_sessions} WHERE courseid = :courseid";
            $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_COURSE) {
            $userids = $userlist->get_userids();
            list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $inparams['courseid'] = $context->instanceid;
            $DB->delete_records_select('block_sence_sessions', "courseid = :courseid AND userid $insql", $inparams);
        }
    }
}
