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
 * Enrol users matching the conditions task.
 *
 * @package   enrol_autoenrol
 * @copyright 2026 Jowin John Chemban
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_autoenrol\task;

/**
 * Class enrol_matching_users
 *
 * Enrols users as soon as they meet the conditions of instances set to enrol on meeting conditions,
 * without waiting for them to log in or to access the course.
 *
 * Each run processes only users created, modified or logged in since the previous run,
 * all users are processed once a day and when an instance settings change.
 *
 * @package   enrol_autoenrol
 * @copyright 2026 Jowin John Chemban
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_matching_users extends \core\task\scheduled_task {
    /** @var int Seconds of overlap with last run when processing only changed users */
    const SINCE_OVERLAP = 300;

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('enrolmatchinguserstask', 'enrol_autoenrol');
    }

    /**
     * Run task for enrolling users that meet the instances conditions.
     *
     * @param int $courseid course id, empty means all courses
     *
     * @return void
     */
    public function execute($courseid = null) {
        global $DB;

        if (!enrol_is_enabled('autoenrol')) {
            mtrace(get_string('pluginnotenabled', 'enrol_autoenrol'));
            return;
        }

        $enrol = enrol_get_plugin('autoenrol');

        $trace = new \text_progress_trace();

        // Usually process only users changed since last run, all users are processed once a day.
        $starttime = time();
        $lastsync = (int)get_config('enrol_autoenrol', 'matchinguserslastsync');
        $lastfullsync = (int)get_config('enrol_autoenrol', 'matchinguserslastfullsync');
        $fullsync = !empty($courseid) || empty($lastsync) || ($starttime - $lastfullsync >= DAYSECS);
        // A small overlap with last run covers clock differences between servers.
        $since = $fullsync ? 0 : $lastsync - self::SINCE_OVERLAP;

        $params = ['enrol' => 'autoenrol', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => 3];
        if (!empty($courseid)) {
            $params['courseid'] = $courseid;
        }
        $instances = $DB->get_records('enrol', $params, 'id');
        foreach ($instances as $instance) {
            $enrol->sync_instance($trace, $instance, $since);
        }

        if (empty($courseid)) {
            set_config('matchinguserslastsync', $starttime, 'enrol_autoenrol');
            if ($fullsync) {
                set_config('matchinguserslastfullsync', $starttime, 'enrol_autoenrol');
            }
        }

        $trace->finished();
    }
}
