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
 * Privacy Subsystem implementation for enrol_autoenrol.
 *
 * @package    enrol_autoenrol
 * @copyright  Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_autoenrol\privacy;

use \core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for enrol_autoenrol.
 *
 * @copyright  2018 Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores user data.
        \core_privacy\local\metadata\provider,

        // This plugin contains user's enrolments.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised item collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_subsystem_link('core_group', [], 'privacy:metadata:core_group');
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {groups_members} gm
                  JOIN {groups} g ON gm.groupid = g.id
                  JOIN {context} ctx ON g.courseid = ctx.instanceid AND ctx.contextlevel = :contextlevel
                 WHERE gm.userid = :userid
                   AND gm.component = 'enrol_autoenrol'";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid'        => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        \core_group\privacy\provider::get_group_members_in_context($userlist, 'enrol_autoenrol');
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        if (empty($contextlist)) {
            return;
        }
        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                \core_group\privacy\provider::export_groups(
                    $context,
                    'enrol_autoenrol',
                    [get_string('pluginname', 'enrol_autoenrol')]
                );
            }
        }
    }

    /**
     * Delete all use data which matches the specified deletion_criteria.
     *
     * @param context $context A user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if (empty($context)) {
            return;
        }
        if ($context->contextlevel == CONTEXT_COURSE) {
            // Delete all the associated groups.
            \core_group\privacy\provider::delete_groups_for_all_users($context, 'enrol_autoenrol');
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }
        \core_group\privacy\provider::delete_groups_for_user($contextlist, 'enrol_autoenrol');
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist   $userlist   The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        \core_group\privacy\provider::delete_groups_for_users($userlist, 'enrol_autoenrol');
    }

}
