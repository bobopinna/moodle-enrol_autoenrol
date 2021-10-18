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

/**
 * Class enrol_autoenrol_edit_form
 *
 * @package    enrol_autoenrol
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onwards Roberto Pinna and Angelo Calò
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_autoenrol_edit_form extends moodleform {

    /**
     * Customdata object
     *
     * @var object
     */
    protected $_customdata;

    /**
     * Form definition
     */
    public function definition() {
        list($instance, $plugin, $context) = $this->_customdata;

        $this->add_hidden_fields();
        $this->add_general_section($instance, $plugin, $context);
        $this->add_filtering_section($plugin);
        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    /**
     * Add the general section to the form
     *
     * @param stdClass $instance
     * @param object $plugin
     * @param object $context
     *
     * @throws coding_exception
     */
    protected function add_general_section($instance, $plugin, $context) {
        global $CFG, $OUTPUT;

        $logourl = '';
        if (method_exists($OUTPUT, 'image_url')) {
            $logourl = $OUTPUT->image_url('logo', 'enrol_autoenrol');
        } else {
            $logourl = $OUTPUT->pix_url('logo', 'enrol_autoenrol');
        }

        $img = html_writer::empty_tag(
                'img',
                array(
                        'src'   => $logourl,
                        'alt'   => 'AutoEnrol Logo',
                        'title' => 'AutoEnrol Logo'
                )
        );
        $img = html_writer::div($img, null, array('style' => 'text-align:center;margin: 1em 0;'));

        $this->_form->addElement('html', $img);
        $this->_form->addElement(
                'static', 'description', html_writer::tag('strong', get_string('warning', 'enrol_autoenrol')),
                get_string('warning_message', 'enrol_autoenrol'));

        $this->_form->addElement('header', 'generalsection', get_string('general'));
        $this->_form->setExpanded('generalsection');

        // Custom instance name.
        $nameattribs = array('size' => '20', 'maxlength' => '255');
        $this->_form->addElement('text', 'name', get_string('instancename', 'enrol_autoenrol'), $nameattribs);
        $this->_form->setType('name', PARAM_TEXT);
        $this->_form->setDefault('name', '');
        $this->_form->addHelpButton('name', 'instancename', 'enrol_autoenrol');
        $this->_form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        // Auto Enrol enabled status.
        $options = $this->get_status_options();
        $this->_form->addElement('select', 'status', get_string('status', 'enrol_autoenrol'), $options);
        $this->_form->addHelpButton('status', 'status', 'enrol_autoenrol');

        // New enrolment enabled.
        $this->_form->addElement('selectyesno', 'customint4', get_string('newenrols', 'enrol_autoenrol'));
        $this->_form->addHelpButton('customint4', 'newenrols', 'enrol_autoenrol');
        $this->_form->setDefault('customint4', $plugin->get_config('newenrols'));
        $this->_form->disabledIf('customint4', 'status', 'eq', ENROL_INSTANCE_DISABLED);

        // Role id.
        if ($instance->id) {
            $options = $this->extend_assignable_roles($context, $instance->roleid);
        } else {
            $options = $this->extend_assignable_roles($context, $plugin->get_config('defaultrole'));
        }
        $this->_form->addElement('select', 'roleid', get_string('role', 'enrol_autoenrol'), $options);
        $this->_form->setDefault('roleid', $plugin->get_config('defaultrole'));
        $this->_form->setType('roleid', PARAM_INT);

        // Enrol When.
        if ($plugin->get_config('loginenrol')) {
            $options = $this->get_enrolmethod_options();
            $this->_form->addElement('select', 'customint1', get_string('method', 'enrol_autoenrol'), $options);
            if (!has_capability('enrol/autoenrol:method', $context)) {
                $this->_form->disabledIf('customint1', 'customchar3', 'eq', '-');
            }
            $this->_form->setType('customint1', PARAM_INT);
            $this->_form->addHelpButton('customint1', 'method', 'enrol_autoenrol');
        }

        // Enrol always.
        $this->_form->addElement('selectyesno', 'customint8', get_string('alwaysenrol', 'enrol_autoenrol'));
        $this->_form->setType('customint8', PARAM_INT);
        $this->_form->setDefault('customint8', 0);
        $this->_form->addHelpButton('customint8', 'alwaysenrol', 'enrol_autoenrol');

        // Self unenrol.
        $this->_form->addElement('selectyesno', 'customint6', get_string('selfunenrol', 'enrol_autoenrol'));
        $this->_form->setType('customint6', PARAM_INT);
        $this->_form->setDefault('customint6', $plugin->get_config('selfunenrol'));
        $this->_form->addHelpButton('customint6', 'selfunenrol', 'enrol_autoenrol');
        $this->_form->disabledIf('customint6', 'customint1', 'eq', '1');

        // Enrol duration.
        $options = array('optional' => true, 'defaultunit' => 86400);
        $this->_form->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_autoenrol'), $options);
        $this->_form->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_autoenrol');
        $this->_form->setDefault('enrolperiod', $plugin->get_config('enrolperiod'));

        // Expire notify.
        $options = $this->get_expirynotify_options();
        $this->_form->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
        $this->_form->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');
        $this->_form->setDefault('expirynotify', $plugin->get_config('expirynotify'));

        // Expire threshold.
        $options = array('optional' => false, 'defaultunit' => 86400);
        $this->_form->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), $options);
        $this->_form->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
        $this->_form->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);
        $this->_form->setDefault('expirythreshold', $plugin->get_config('expirythreshold'));

        // Start date.
        $options = array('optional' => true);
        $this->_form->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_autoenrol'), $options);
        $this->_form->setDefault('enrolstartdate', 0);
        $this->_form->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_autoenrol');

        // End date.
        $this->_form->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_autoenrol'), $options);
        $this->_form->setDefault('enrolenddate', 0);
        $this->_form->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_autoenrol');

        // Longtime no see.
        $options = $this->get_longtimenosee_options();
        $this->_form->addElement('select', 'customint3', get_string('longtimenosee', 'enrol_autoenrol'), $options);
        $this->_form->addHelpButton('customint3', 'longtimenosee', 'enrol_autoenrol');
        $this->_form->setDefault('customint3', $plugin->get_config('longtimenosee'));

        // Welcome message sending.
        if (function_exists('enrol_send_welcome_email_options')) {
            $options = enrol_send_welcome_email_options();
            unset($options[ENROL_SEND_EMAIL_FROM_KEY_HOLDER]);
            $this->_form->addElement('select', 'customint7',
                    get_string('sendcoursewelcomemessage', 'enrol_autoenrol'), $options);
        } else {
            $this->_form->addElement('checkbox', 'customint7', get_string('sendcoursewelcomemessage', 'enrol_autoenrol'));
        }
        $this->_form->setDefault('customint7', $plugin->get_config('sendcoursewelcomemessage'));

        // Welcome message text.
        $this->_form->addElement('textarea', 'customtext1',
                get_string('customwelcomemessage', 'enrol_autoenrol'), array('cols' => '60', 'rows' => '8'));
        $this->_form->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_autoenrol');
    }

    /**
     * Add filtering section to form
     *
     * @param object $plugin plugin object
     *
     * @throws coding_exception
     * return void
     */
    protected function add_filtering_section($plugin) {
        global $DB, $COURSE;

        $this->_form->addElement('header', 'filtersection', get_string('filtering', 'enrol_autoenrol'));
        $this->_form->setExpanded('filtersection', true);

        // Use this code to add the 'Restrict access' section.
        // NOTE: Due to limitations in the JavaScript and CSS, you may only
        // have one of these fields on a page! Sorry.
        $this->_form->addElement('textarea', 'availabilityconditionsjson',
                get_string('userfilter', 'enrol_autoenrol'));
        $this->_form->addHelpButton('availabilityconditionsjson', 'userfilter', 'enrol_autoenrol');
        \enrol_autoenrol\filter_frontend::include_all_javascript($COURSE);

        $options = $this->get_groupon_options();
        $this->_form->addElement('select', 'customchar3', get_string('groupon', 'enrol_autoenrol'), $options);
        $this->_form->setType('customchar3', PARAM_TEXT);
        $this->_form->addHelpButton('customchar3', 'groupon', 'enrol_autoenrol');

        $groupnameattribs = array('size' => '20', 'maxlength' => '100');
        $this->_form->addElement('text', 'customchar1', get_string('groupname', 'enrol_autoenrol'), $groupnameattribs);
        $this->_form->setDefault('customchar1', '');
        $this->_form->setType('customchar1', PARAM_TEXT);
        $this->_form->addHelpButton('customchar1', 'groupname', 'enrol_autoenrol');
        $this->_form->disabledIf('customchar1', 'customchar3', 'ne', 'userfilter');
        $this->_form->addRule('customchar1', get_string('maximumchars', '', 100), 'maxlength', 100, 'server');

        $this->_form->addElement('text', 'customint5', get_string('countlimit', 'enrol_autoenrol'));
        $this->_form->setType('customint5', PARAM_INT);
        $this->_form->setDefault('customint5', $plugin->get_config('maxenrolled'));
        $this->_form->addHelpButton('customint5', 'countlimit', 'enrol_autoenrol');
    }

    /**
     * Add hidden fields
     *
     * @return void
     */
    protected function add_hidden_fields() {
        $this->_form->addElement('hidden', 'id');
        $this->_form->setType('id', PARAM_INT);
        $this->_form->addElement('hidden', 'courseid');
        $this->_form->setType('courseid', PARAM_INT);
    }


    /**
     * Validate submitted settings
     *
     * @param array $data Submitted data
     * @param array $files Submitted files
     *
     * @return array Errors list
     */
    public function validation($data, $files) {
        $errors = array();

        list($instance, $plugin, $context) = $this->_customdata;

        // Use this code to validate the 'User Filtering' section.
        \core_availability\frontend::report_validation_errors($data, $errors);

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_autoenrol');
            }
        }

        if ($data['expirynotify'] > 0 and $data['expirythreshold'] < 86400) {
            $errors['expirythreshold'] = get_string('errorthresholdlow', 'core_enrol');
        }

        // Now these ones are checked by quickforms, but we may be called by the upload enrolments tool, or a webservice.
        if (core_text::strlen($data['name']) > 255) {
            $errors['name'] = get_string('err_maxlength', 'form', 255);
        }
        if (core_text::strlen($data['customchar1']) > 100) {
            $errors['customchar1'] = get_string('err_maxlength', 'form', 100);
        }

        $validstatus = array_keys($this->get_status_options());
        $context = context_course::instance($instance->courseid);
        $validroles = array_keys($this->extend_assignable_roles($context, $data['roleid']));
        $validexpirynotify = array_keys($this->get_expirynotify_options());
        $validenrolmethod = array_keys($this->get_enrolmethod_options());
        $validlongtimenosee = array_keys($this->get_longtimenosee_options());
        $validgroupon = array_keys($this->get_groupon_options());
        $validyesno = array(0, 1);
        $tovalidate = array(
            'name' => PARAM_TEXT,
            'status' => $validstatus,
            'roleid' => $validroles,
            'enrolperiod' => PARAM_INT,
            'expirynotify' => $validexpirynotify,
            'enrolstartdate' => PARAM_INT,
            'enrolenddate' => PARAM_INT,
            'customint1' => $validenrolmethod,
            'customint3' => $validlongtimenosee,
            'customint4' => $validyesno,
            'customint5' => PARAM_INT,
            'customint6' => $validyesno,
            'customint7' => PARAM_INT,
            'customint8' => $validyesno,
            'customchar1' => PARAM_TEXT,
            'customchar3' => $validgroupon
        );
        if ($data['expirynotify'] != 0) {
            $tovalidate['expirythreshold'] = PARAM_INT;
        }
        $typeerrors = $plugin->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        return $errors;
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }

    /**
     * Gets a list of roles that this user can assign for the course as the default for self-enrolment.
     *
     * @param context $context the context.
     * @param integer $defaultrole the id of the role that is set as the default for self-enrolment
     * @return array index is the role id, value is the role name
     */
    public function extend_assignable_roles($context, $defaultrole) {
        global $DB;

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        if (!isset($roles[$defaultrole])) {
            if ($role = $DB->get_record('role', array('id' => $defaultrole))) {
                $roles[$defaultrole] = role_get_name($role, $context, ROLENAME_BOTH);
            }
        }
        return $roles;
    }

    /**
     * Return an array of valid options for the expirynotify property.
     *
     * @return array
     */
    protected function get_expirynotify_options() {
        $options = array(0 => get_string('no'),
                         1 => get_string('expirynotifyenroller', 'enrol_autoenrol'),
                         2 => get_string('expirynotifyall', 'enrol_autoenrol'));
        return $options;
    }

    /**
     * Return an array of valid options for the enrol method property.
     *
     * @return array
     */
    protected function get_enrolmethod_options() {
        $options = array( 0 => get_string('m_course', 'enrol_autoenrol'),
                          1 => get_string('m_site', 'enrol_autoenrol'),
                          2 => get_string('m_confirmation', 'enrol_autoenrol'));
        return $options;
    }

    /**
     * Return an array of valid options for the longtimenosee property.
     *
     * @return array
     */
    protected function get_longtimenosee_options() {
        $options = array(0 => get_string('never'),
                         1800 * 3600 * 24 => get_string('numdays', '', 1800),
                         1000 * 3600 * 24 => get_string('numdays', '', 1000),
                         365 * 3600 * 24 => get_string('numdays', '', 365),
                         180 * 3600 * 24 => get_string('numdays', '', 180),
                         150 * 3600 * 24 => get_string('numdays', '', 150),
                         120 * 3600 * 24 => get_string('numdays', '', 120),
                         90 * 3600 * 24 => get_string('numdays', '', 90),
                         60 * 3600 * 24 => get_string('numdays', '', 60),
                         30 * 3600 * 24 => get_string('numdays', '', 30),
                         21 * 3600 * 24 => get_string('numdays', '', 21),
                         14 * 3600 * 24 => get_string('numdays', '', 14),
                         7 * 3600 * 24 => get_string('numdays', '', 7));
        return $options;
    }

    /**
     * Return an array of valid options for the longtimenosee property.
     *
     * @return array
     */
    protected function get_groupon_options() {
        global $DB;

        $options = array('-' => get_string('nogroupon', 'enrol_autoenrol'),
                         'userfilter' => get_string('userfilter', 'enrol_autoenrol'),
                         'auth' => get_string('authentication'),
                         'lang' => get_string('language'),
                         'department' => get_string('department'),
                         'institution' => get_string('institution'),
                         'address' => get_string('address'),
                         'city' => get_string('city'),
                         'email' => get_string('email'));

        $customfields = $DB->get_records('user_info_field');
        if (!empty($customfields)) {
            foreach ($customfields as $customfield) {
                $options[$customfield->shortname] = $customfield->name;
            }
        }
        return $options;
    }

}
