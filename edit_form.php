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

/**
 * Class enrol_autoenrol_edit_form
 */
class enrol_autoenrol_edit_form extends moodleform {

    /**
     *
     */
    public function definition() {
        list($instance, $plugin, $context) = $this->_customdata;

        $this->add_hidden_fields();
        $this->add_general_section($instance, $plugin, $context);
        $this->add_filtering_section();
        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    /**
     * @param $instance
     * @param $plugin
     * @param $context
     *
     * @throws coding_exception
     */
    protected function add_general_section($instance, $plugin, $context) {
        global $CFG, $OUTPUT;

        $this->_form->addElement('header', 'generalsection', get_string('general', 'enrol_autoenrol'));
        $this->_form->setExpanded('generalsection');

        $img = html_writer::empty_tag(
                'img',
                array(
                        'src'   => $OUTPUT->pix_url('logo', 'enrol_autoenrol'),
                        'alt'   => 'AutoEnrol Logo',
                        'title' => 'AutoEnrol Logo'
                )
        );
        $img = html_writer::div($img, null, array('style' => 'text-align:center;margin: 1em 0;'));

        $this->_form->addElement('html', $img);
        $this->_form->addElement(
                'static', 'description', html_writer::tag('strong', get_string('warning', 'enrol_autoenrol')),
                get_string('warning_message', 'enrol_autoenrol'));

        $this->_form->addElement('text', 'customchar2', get_string('instancename', 'enrol_autoenrol'));
        $this->_form->setType('customchar2', PARAM_TEXT);
        $this->_form->setDefault('customchar2', '');
        $this->_form->addHelpButton('customchar2', 'instancename', 'enrol_autoenrol');

        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $this->_form->addElement('select', 'customint3', get_string('role', 'enrol_autoenrol'), $roles);
        $this->_form->setAdvanced('customint3');
        $this->_form->addHelpButton('customint3', 'role', 'enrol_autoenrol');
        if (!has_capability('enrol/autoenrol:method', $context)) {
            $this->_form->disabledIf('customint3', 'customint2');
        }
        $this->_form->setDefault('customint3', $plugin->get_config('defaultrole'));
        $this->_form->setType('customint3', PARAM_INT);

        $method = array(get_string('m_course', 'enrol_autoenrol'), get_string('m_site', 'enrol_autoenrol'));
        $this->_form->addElement('select', 'customint1', get_string('method', 'enrol_autoenrol'), $method);
        if (!has_capability('enrol/autoenrol:method', $context)) {
            $this->_form->disabledIf('customint1', 'customint2');
        }
        $this->_form->setAdvanced('customint1');
        $this->_form->setType('customint1', PARAM_INT);
        $this->_form->addHelpButton('customint1', 'method', 'enrol_autoenrol');

        $this->_form->addElement('selectyesno', 'customint8', get_string('alwaysenrol', 'enrol_autoenrol'));
        $this->_form->setAdvanced('customint8');
        $this->_form->setType('customint8', PARAM_INT);
        $this->_form->setDefault('customint8', 0);
        $this->_form->addHelpButton('customint8', 'alwaysenrol', 'enrol_autoenrol');
    }

    /**
     * @throws coding_exception
     */
    protected function add_filtering_section() {
        $this->_form->addElement('header', 'filtersection', get_string('filtering', 'enrol_autoenrol'));
        $this->_form->setExpanded('filtersection', false);

        $fields = array(get_string('g_none', 'enrol_autoenrol'),
                get_string('g_auth', 'enrol_autoenrol'),
                get_string('g_dept', 'enrol_autoenrol'),
                get_string('g_inst', 'enrol_autoenrol'),
                get_string('g_lang', 'enrol_autoenrol'),
                get_string('g_email', 'enrol_autoenrol'));

        $this->_form->addElement('select', 'customint2', get_string('groupon', 'enrol_autoenrol'), $fields);
        $this->_form->setType('customint2', PARAM_INT);
        $this->_form->addHelpButton('customint2', 'groupon', 'enrol_autoenrol');

        $this->_form->addElement('text', 'customchar1', get_string('filter', 'enrol_autoenrol'));
        $this->_form->setDefault('customchar1', '');
        $this->_form->setType('customchar1', PARAM_TEXT);
        $this->_form->addHelpButton('customchar1', 'filter', 'enrol_autoenrol');
        $this->_form->disabledIf('customchar1', 'customint2', 'eq', 0);

        $this->_form->addElement('selectyesno', 'customint4', get_string('softmatch', 'enrol_autoenrol'));
        $this->_form->setDefault('customint4', 0);
        $this->_form->addHelpButton('customint4', 'softmatch', 'enrol_autoenrol');

        $this->_form->addElement('text', 'customint5', get_string('countlimit', 'enrol_autoenrol'));
        $this->_form->setType('customint5', PARAM_INT);
        $this->_form->setDefault('customint5', 0);
        $this->_form->addHelpButton('customint5', 'countlimit', 'enrol_autoenrol');
    }

    /**
     *
     */
    protected function add_hidden_fields() {
        $this->_form->addElement('hidden', 'id');
        $this->_form->setType('id', PARAM_INT);
        $this->_form->addElement('hidden', 'courseid');
        $this->_form->setType('courseid', PARAM_INT);
    }

    /**
     * @type occurrence
     */
    protected $_customdata;
}
