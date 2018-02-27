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
 * autoenrol enrolment plugin.
 *
 * This plugin automatically enrols a user onto a course the first time they try to access it.
 *
 * @package    enrol_autoenrol
 * @copyright  2014 Mark Ward - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Auto Enrol pluing upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_enrol_autoenrol_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2014113000) {

        $filtertype = array(get_string('g_none', 'enrol_autoenrol'),
            get_string('g_auth', 'enrol_autoenrol'),
            get_string('g_dept', 'enrol_autoenrol'),
            get_string('g_inst', 'enrol_autoenrol'),
            get_string('g_lang', 'enrol_autoenrol'),
            get_string('g_email', 'enrol_autoenrol'));

        $instances = $DB->get_records('enrol', array('enrol' => 'autoenrol'));

        foreach ($instances as $instance) {
            $groupids = explode(',', $instance->customtext1);

            // Ensure that each groupid is a valid int.
            foreach ($groupids as $key => $groupid) {
                if (empty($groupid) || !is_int($groupid)) {
                    unset($groupids[$key]);
                } else {
                    $groupids[$key] = (int) $groupid;
                }
            }

            if (empty($groupids)) {
                continue;
            }

            $groups = $DB->get_records_list('groups', 'id', $groupids);

            foreach ($groups as $group) {
                $group->name = str_replace('Auto|', '', $group->name);

                if (!strlen($group->name)) {
                    $group->name = get_string('emptyfield', 'enrol_autoenrol', $filtertype[$instance->customint2]);
                }

                $group->idnumber = "autoenrol|$instance->id|$group->name";
                $DB->update_record('groups', $group);
            }

            $instance->customtext1 = null;
            $DB->update_record('enrol', $instance);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2014113000, 'enrol', 'autoenrol');
    }

    if ($oldversion < 2016122000) {

        $fields = array();
        $fields[] = '-';
        $fields[] = 'auth';
        $fields[] = 'department';
        $fields[] = 'institution';
        $fields[] = 'lang';
        $fields[] = 'email';

        $instances = $DB->get_records('enrol', array('enrol' => 'autoenrol'));

        foreach ($instances as $instance) {
            if (isset($instance->customint2)) {
                $instance->customchar3 = $fields[$instance->customint2];
            }

            $DB->update_record('enrol', $instance);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2016122000, 'enrol', 'autoenrol');
    }

    if ($oldversion < 2017122500) {
        upgrade_plugin_savepoint(true, 2017122500, 'enrol', 'autoenrol');
    }

    if ($oldversion < 2018022600) {
        upgrade_plugin_savepoint(true, 2018022600, 'enrol', 'autoenrol');
    }

    return true;
    return true;
}
