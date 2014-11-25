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

class enrol_autoenrol_plugin extends enrol_plugin {

    public function get_info_icons(array $instances) {
        return array(new pix_icon('icon', get_string('pluginname', 'enrol_autoenrol'), 'enrol_autoenrol'));
    }

    public function roles_protected() {
        // users with role assign cap may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        // users with unenrol cap may unenrol other users manually - requires enrol/autoenrol:unenrol
        return false;
    }

    public function allow_manage(stdClass $instance) {
        // users with manage cap may tweak period and status - requires enrol/autoenrol:manage
        return false;
    }

    public function show_enrolme_link(stdClass $instance) {
        return false;
		//return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Sets up navigation entries.
     *
     * @param object $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
		global $USER;
		if ($instance->enrol !== 'autoenrol') {
             throw new coding_exception('Invalid enrol instance type!');
        }
		if ($instance->customint1 != 1 && $this->enrol_allowed($USER, $instance)){
			$this->enrol_user($instance, $USER->id, $instance->customint3, time(), 0);
			$this->do_group($instance);
		}
        $context = get_context_instance(CONTEXT_COURSE, $instance->courseid);
        if (has_capability('enrol/autoenrol:config', $context)) {
            $managelink = new moodle_url('/enrol/autoenrol/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'autoenrol') {
            throw new coding_exception('invalid enrol instance!');
        }
		$context = get_context_instance(CONTEXT_COURSE, $instance->courseid);
        $icons = array();
		
        if (has_capability('enrol/autoenrol:config', $context)) {
            $editlink = new moodle_url("/enrol/autoenrol/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('i/edit', get_string('edit'), 'core', array('class'=>'icon')));
        }

        return $icons;
    }
	
	/**
     * This is important especially for external enrol plugins, 
	 * this function is called for all enabled enrol plugins 
	 * right after every user login.
     *
     * @param object $user user record
     * @return void	 
     */
    public function sync_user_enrolments($user) {
		global $DB;
		
		//this process takes 0.01 seconds on average

		//Get records of all the AutoEnrolment instances which are set to enrol at login
		$instances = $DB->get_records('enrol', array('enrol'=>'autoenrol', 'customint1'=>1), NULL, '*');	
		//Now get a record of all of the users enrolments
		$user_enrolments = $DB->get_records('user_enrolments', array('userid'=>$user->id), NULL, '*');	
		//run throuch all of the autoenrolment instances and check that the user has been enrolled.		
		foreach ($instances as $instance){
			$found = FALSE;
			foreach ($user_enrolments as $user_enrolment){
				if($user_enrolment->enrolid == $instance->id){
					$found = TRUE;
				}
			}
			
			if (!$found && $this->enrol_allowed($user, $instance)){
				$this->enrol_user($instance, $user->id, $instance->customint3, time(), 0);
				$this->do_group($instance);
			}
				
		}
	}

    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        return get_string('pluginname', 'enrol_autoenrol');
    }    
    
    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
		global $DB;
		
        $context = get_context_instance(CONTEXT_COURSE, $courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/autoenrol:config', $context)) {
            return NULL;
        }
		
		//Should return null if the plugin is already installed
		if ($DB->record_exists('enrol', array('courseid'=>$courseid, 'enrol'=>'autoenrol'))) {
            return NULL;
        }

        // multiple instances supported - different cost for different roles
        return new moodle_url('/enrol/autoenrol/edit.php', array('courseid'=>$courseid));
    }
	
    /**
     * Intercepts the instance deletion call and gives some
	 * custom instructions before resuming the parent function
     */
   public function delete_instance($instance) {
        global $DB;		
		require_once("../group/lib.php");	
		
		$groups = explode(',',$instance->customtext1);
		foreach($groups as $group){	
			if($DB->record_exists('groups', array('id'=>$group))){
				groups_delete_group($group);		
			}		
		}
 		parent::delete_instance($instance);		
    }
		

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
		global $USER;
		if ($instance->customint1 != 1 && $this->enrol_allowed($USER,$instance)){
			$this->enrol_user($instance, $USER->id, $instance->customint3, time(), 0);
			$this->do_group($instance);
		}
        return NULL;
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        $fields = array('status'=>0, 'customint3'=>$this->get_config('defaultrole'));
        return $this->add_instance($course, $fields);
    }
	
    /**
     * Custom function, checks to see if user fulfills
     * our requirements before enrolling them.
     */
	public function enrol_allowed($USER, stdClass $instance){
        global $DB;		
	    	
		if (isguestuser()) {
            // can not enrol guest!!
            return FALSE;
        }
		
		//very quick check to see if the user is being filtered
		if ($instance->customchar1!=''){
			if(!is_object($USER)){
				return FALSE;
			}
			if(!isset($USER->auth)){
				$USER->auth = '';
			}
			
			if(!isset($USER->department)){
				$USER->department = '';
			}

			if(!isset($USER->institution)){
				$USER->institution = '';
			}

			if(!isset($USER->lang)){
				$USER->lang = '';
			}
			$type = array(1=>$USER->auth, $USER->department, $USER->institution, $USER->lang);
			if($instance->customchar1!=$type[$instance->customint2]){
				return FALSE;
				
			}
		}		
		
        if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
            return FALSE;
        }

        if ($instance->enrolstartdate != 0 and $instance->enrolstartdate > time()) {
             return FALSE;
        }

        if ($instance->enrolenddate != 0 and $instance->enrolenddate < time()) {
            return FALSE;
        }
		return TRUE;
	}
	
	private function do_group(stdClass $instance){
        global $CFG, $USER, $DB;					
		if (isloggedin()){  
			//debugging("trying to enrol user now");			
			if($instance->customint2 != 0){
				
				$type = array(1=>$USER->auth, $USER->department, $USER->institution, $USER->lang);
						
				require_once($CFG->dirroot.'/group/lib.php');	
				
				$name = get_string('auto', 'enrol_autoenrol').'|'.$type[$instance->customint2];
				//debugging($name);
				
				$group = $DB->get_record('groups', array('name'=>$name, 'courseid'=>$instance->courseid));
				if($group == NULL){
					//debugging("group not found, adding it now");
					$newgroupdata -> courseid = $instance->courseid;
					$newgroupdata -> name = $name;
					$newgroupdata -> description = get_string('auto_desc', 'enrol_autoenrol');		
					$group = groups_create_group($newgroupdata);				
					//keep a record of what we have created, deleting random groups just wouldn't be cricket!
					$instance->customtext1 = $group.','.$instance->customtext1;
					$DB->update_record('enrol', $instance);	
				}
				else{
					$group = $group->id;
				}
				groups_add_member($group, $USER->id);	
		
				
				add_to_log($instance->courseid, 'course', 'enrol', '../enrol/users.php?id='.$instance->courseid, $instance->courseid);
				
			} else {
			  //debugging("this shouldnt be possible!");
			 return null;
			}
		} else {
		  //debugging("this shouldnt be possible!");
		 return null;
		}
		return null;
	}
}
