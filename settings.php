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
 * @package    enrol
 * @subpackage autoenrol
 * @author     Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @date       July 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('enrol_autoenrol_settings', '', get_string('pluginname_desc', 'enrol_autoenrol')));

    $settings->add(
        new admin_setting_configcheckbox(
            'enrol_autoenrol/defaultenrol',
            get_string('defaultenrol', 'enrol'),
            get_string('defaultenrol_desc', 'enrol'),
            0)
    );

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(
                new admin_setting_configselect(
                    'enrol_autoenrol/defaultrole',
                    get_string('defaultrole', 'enrol_autoenrol'),
                    get_string('defaultrole_desc', 'enrol_autoenrol'),
                    $student->id,
                    $options
                )
        );
    }

    $settings->add(
        new admin_setting_configcheckbox(
            'enrol_autoenrol/removegroups',
            get_string('removegroups', 'enrol_autoenrol'),
            get_string('removegroups_desc', 'enrol_autoenrol'),
            1
        )
    );
}
