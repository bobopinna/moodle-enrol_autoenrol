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
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onwards Roberto Pinna
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

    $options = array(
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
        ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPEND        => get_string('extremovedsuspend', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol')
    );
    $settings->add(
        new admin_setting_configselect(
            'enrol_autoenrol/autounenrolaction',
            get_string('autounenrolaction', 'enrol_autoenrol'),
            get_string('autounenrolaction_help', 'enrol_autoenrol'),
            ENROL_EXT_REMOVED_UNENROL,
            $options
        )
    );

    if (function_exists('enrol_send_welcome_email_options')) {
        $options = enrol_send_welcome_email_options();
        unset($options[ENROL_SEND_EMAIL_FROM_KEY_HOLDER]);
        $settings->add(
            new admin_setting_configselect('enrol_autoenrol/sendcoursewelcomemessage',
                get_string('sendcoursewelcomemessage', 'enrol_autoenrol'),
                get_string('sendcoursewelcomemessage_help', 'enrol_autoenrol'),
                ENROL_DO_NOT_SEND_EMAIL,
                $options
            )
        );
    } else {
        $settings->add(
            new admin_setting_configcheckbox('enrol_autoenrol/sendcoursewelcomemessage',
                get_string('sendcoursewelcomemessage', 'enrol_autoenrol'),
                get_string('sendcoursewelcomemessage_help', 'enrol_autoenrol'),
                0
            )
        );
    }
}
