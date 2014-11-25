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
 * @date       October 2011
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class enrol_autoenrol_edit_form extends moodleform {

    function definition() {
		global $CFG;
        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('config', 'enrol_autoenrol'));	
		$mform->addElement('html', "<div style='text-align:center'><img src='".$CFG->wwwroot."/theme/image.php?image=logo&component=enrol_autoenrol' alt='AutoEnrol Logo' title='AutoEnrol Logo' /></div><br />");
		$mform->addElement('static', 'description', '<b>'.get_string('warning', 'enrol_autoenrol').'</b>',
                  get_string('warning_message', 'enrol_autoenrol'));
		$mform->addElement('html', '<br />');	  
				
				
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $mform->addElement('select', 'customint3', get_string('role', 'enrol_autoenrol'), $roles);		
		$mform->setAdvanced('customint3');
		$mform->addHelpButton('customint3', 'role', 'enrol_autoenrol');		
        if (!has_capability('enrol/autoenrol:method', $context))
			$mform->disabledIf('customint3', 'customint2');
        $mform->setDefault('customint3', $plugin->get_config('defaultrole'));	
						
		
		$method = array(get_string('m_course', 'enrol_autoenrol'),get_string('m_site', 'enrol_autoenrol'));		
        $mform->addElement('select', 'customint1', get_string('method', 'enrol_autoenrol'), $method);	
        if (!has_capability('enrol/autoenrol:method', $context))
			$mform->disabledIf('customint1', 'customint2');
		$mform->setAdvanced('customint1');
		$mform->addHelpButton('customint1', 'method', 'enrol_autoenrol');
		 	
		$fields = array(get_string('g_none', 'enrol_autoenrol'),get_string('g_auth', 'enrol_autoenrol'),get_string('g_dept', 'enrol_autoenrol'),get_string('g_inst', 'enrol_autoenrol'),get_string('g_lang', 'enrol_autoenrol'));		
        $mform->addElement('select', 'customint2', get_string('groupon', 'enrol_autoenrol'), $fields);	
		$mform->setAdvanced('customint2');		
		$mform->addHelpButton('customint2', 'groupon', 'enrol_autoenrol');		
					  
		$mform->addElement('text','customchar1', get_string('filter', 'enrol_autoenrol'));
		$mform->setAdvanced('customchar1');		
		$mform->addHelpButton('customchar1', 'filter', 'enrol_autoenrol');	
		$mform->disabledIf('customchar1', 'customint2', 'eq', 0);
					  
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }
}