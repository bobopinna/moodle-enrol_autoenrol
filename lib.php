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
 * Autoenrol enrolment plugin.
 *
 * This plugin automatically enrols a user onto a course the first time they try to access it.
 *
 * @package    enrol_autoenrol
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_autoenrol\enrol_form;

/**
 * Class enrol_autoenrol_plugin
 *
 * @package    enrol_autoenrol
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @copyright  2017 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_autoenrol_plugin extends enrol_plugin {

    /**
     * Database fields mapping
     *
     * name => Instance name
     * status => Instance status (enabled/disabled)
     * roleid => User role id
     * enrolperiod => Enrolment duration/until date
     * expirenotify => Who need to be notified on expire
     * expirethreshold => How many minutes before expire need to send notify
     * enrolstartdate => When start to enrol
     * enrolenddate => When stop to enrol
     * customint1 => Enrol on course access or on login
     * customint2 => -- NOT USED -- Old group field filter
     * customint3 => Longtime no see unenrol
     * customint4 => New enrolment enabled
     * customint5 => Enrolment Limit number
     * customint6 => Enable self unenrol
     * customint7 => Welcome message enabled
     * customint8 => Always enrol
     * customchar1 => Group Name
     * customchar2 => -- NOT USED --
     * customchar3 => Group by field name
     * customtext1 => Welcome message
     * customtext2 => Conditional rule
     */

    /**
     * Get enrol method icon
     *
     * @param array $instances
     *
     * @return array
     */
    public function get_info_icons(array $instances) {
        return array(new pix_icon('icon', get_string('pluginname', 'enrol_autoenrol'), 'enrol_autoenrol'));
    }

    /**
     * Users with role assign cap may tweak the roles later.
     *
     * @return bool
     */
    public function roles_protected() {
        return false;
    }

    /**
     * Users with unenrol cap may unenrol other users manually - requires enrol/autoenrol:unenrol.
     *
     * @param stdClass $instance
     *
     * @return bool
     */
    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * Users with manage cap may tweak period and status - requires enrol/autoenrol:manage.
     *
     * @param stdClass $instance
     *
     * @return bool
     */
    public function allow_manage(stdClass $instance) {
        return true;
    }

    /**
     * Must show enrolme link.
     *
     * @param stdClass $instance
     *
     * @return bool
     */
    public function show_enrolme_link(stdClass $instance) {
        if ($this->get_config('loginenrol') && $instance->customint1 == 1) {
            // Don't offer enrolself if we are going to enrol them on login.
            return false;
        }
        return true;
    }

    /**
     * Returns list of unenrol links for all enrol instances in course.
     *
     * @param int $instance
     *
     * @return moodle_url or NULL if self unenrolment not supported
     */
    public function get_unenrolself_link($instance) {
        if (($this->get_config('loginenrol') && ($instance->customint1 == 1)) || ($instance->customint6 == 0)) {
            // Don't offer unenrolself if we are going to re-enrol them on login or if not permitted.
            return null;
        }
        return parent::get_unenrolself_link($instance);
    }


    /**
     * Attempt to automatically enrol current user in course without any interaction,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course enrol instance
     *
     * @return bool|int false means not enrolled, integer means timeend
     * @throws coding_exception
     */
    public function try_autoenrol(stdClass $instance) {
        global $USER, $CFG;

        if (!defined('ENROL_DO_NOT_SEND_EMAIL')) {
            define('ENROL_DO_NOT_SEND_EMAIL', 0);
        }

        if (($CFG->branch < 32) || ($instance->customint7 == ENROL_DO_NOT_SEND_EMAIL)) {
            if ($this->user_autoenrol($instance, $USER)) {
                return 0;
            }
        }
        return false;
    }


    /**
     * Custom function, checks to see if user fulfills
     * our requirements before enrolling them.
     *
     * @param object $user
     * @param stdClass $instance
     *
     * @return bool
     */
    public function enrol_allowed($user, stdClass $instance) {
        global $DB;

        if (isguestuser()) {
            // Can not enrol guest!!
            return false;
        }

        // Do not reenrol if already enrolled with this method.
        if ($DB->record_exists('user_enrolments', array('userid' => $user->id, 'enrolid' => $instance->id))) {
            return false;
        }

        if (!$instance->customint8) {
            // Do not reenrol if already enrolled with another method.
            $context = context_course::instance($instance->courseid);
            if (is_enrolled($context, $user, 'moodle/course:view')) {
                // No need to enrol someone who is already enrolled.
                return false;
            }
        }

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return false;
        }

        if (!$instance->customint4) {
            // New enrols not allowed.
            return false;
        }

        if ($instance->enrolstartdate != 0 and $instance->enrolstartdate > time()) {
            return false;
        }

        if ($instance->enrolenddate != 0 and $instance->enrolenddate < time()) {
            return false;
        }

        if ($instance->customint5 > 0) {
            // We need to check that we haven't reached the limit count.
            $totalenrolments = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
            if ($totalenrolments >= $instance->customint5) {
                return false;
            }
        }

        if (!$this->check_rule($instance, $user)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if user field match the rule.
     *
     * @param stdClass $instance
     * @param object   $user
     *
     * @return bool
     */
    private function check_rule($instance, $user) {
        global $CFG;

        // Very quick check to see if the user is being filtered.
        if (!empty($instance->customtext2)) {
            if (!is_object($user)) {
                return false;
            }

            $info = new \enrol_autoenrol\filter_info($instance);
            $information = '';

            return $info->is_available($information, false, $user->id);
        }
        return true;
    }

    /**
     * Attempt to enrol a user in course and update groups enrolments,
     * calling code has to make sure the plugin and instance are active.
     *
     * @param stdClass $instance course enrol instance
     * @param stdClass $user     user data
     *
     * @return bool true means enrolled, false means not enrolled
     * @throws coding_exception
     */
    public function user_autoenrol(stdClass $instance, stdClass $user) {
        if ($instance->customint1 == 2) {
            return false;
        }

        if (!defined('ENROL_DO_NOT_SEND_EMAIL')) {
            define('ENROL_DO_NOT_SEND_EMAIL', 0);
        }
        if ($instance->enrol !== 'autoenrol') {
            throw new coding_exception('Invalid enrol instance type!');
        }
        if ($this->enrol_allowed($user, $instance)) {
            $timestart = time();
            if ($instance->enrolperiod > 0) {
                $timeend = $timestart + $instance->enrolperiod;
            } else if ($instance->enrolperiod < 0) {
                $timeend = -$instance->enrolperiod;
            } else {
                $timeend = 0;
            }
            $this->enrol_user($instance, $user->id, $instance->roleid, $timestart, $timeend);
            $this->process_group($instance, $user);
            // Send welcome message.
            if ($instance->customint7 != ENROL_DO_NOT_SEND_EMAIL) {
                $this->email_welcome_message($instance, $user);
            }
            return true;
        }
        return false;
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass                 $ue A user enrolment object
     *
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol_user($instance, $ue) && has_capability('enrol/autoenrol:unenrol', $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(
                    new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url,
                    array('class' => 'unenrollink', 'rel' => $ue->id));
        }
        return $actions;
    }

    /**
     * Sets up navigation entries.
     *
     * @param object   $instancesnode
     * @param stdClass $instance
     *
     * @throws coding_exception
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'autoenrol') {
            throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/autoenrol:config', $context)) {
            $managelink = new moodle_url(
                    '/enrol/autoenrol/edit.php', array('courseid' => $instance->courseid, 'id' => $instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }


    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     *
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance->name)) {
            if (!empty($instance->roleid) and $role = $DB->get_record('role', array('id' => $instance->roleid))) {
                $role = ' (' . role_get_name($role, context_course::instance($instance->courseid, IGNORE_MISSING)) . ')';
            } else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol) . $role;
        } else {
            return format_string($instance->name);
        }
    }

    /**
     * Returns edit icons for the page with list of instances
     *
     * @param stdClass $instance
     *
     * @return array
     * @throws coding_exception
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'autoenrol') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);
        $icons = array();

        if (has_capability('enrol/autoenrol:config', $context)) {
            $editlink = new moodle_url(
                    '/enrol/autoenrol/edit.php', array('courseid' => $instance->courseid, 'id' => $instance->id));
            $icons[] = $OUTPUT->action_icon(
                    $editlink, new pix_icon('t/edit', get_string('edit'), 'core', array('class' => 'iconsmall')));
        }

        return $icons;
    }

    /**
     * This is important especially for external enrol plugins,
     * this function is called for all enabled enrol plugins
     * right after every user login.
     *
     * @param object         $user user record
     * @param boolean        $onlogin standard on login or scheduled call
     * @param int            $course course id
     *
     * @return void
     */
    public function sync_user_enrolments($user, $onlogin=true, $course = null) {
        global $DB, $PAGE;

        $instances = array();
        if (!empty($course)) {
            // Get records of all enabled the AutoEnrol instances in a specified course.
            $instances = $DB->get_records('enrol', array('enrol' => 'autoenrol', 'status' => 0, 'courseid' => $course), null, '*');
        } else {
            // Get records of all enabled the AutoEnrol instances.
            $instances = $DB->get_records('enrol', array('enrol' => 'autoenrol', 'status' => 0), null, '*');
        }
        // Now get a record of all of the users enrolments.
        $userenrolments = $DB->get_records('user_enrolments', array('userid' => $user->id), null, '*');
        // Run through all of the autoenrolment instances and check that the user has been enrolled.
        foreach ($instances as $instance) {
            $found = false;
            foreach ($userenrolments as $userenrolment) {
                if ($userenrolment->enrolid == $instance->id) {
                    $found = true;
                }
            }

            if (!$found && (($this->get_config('loginenrol') && ($instance->customint1 == 1)) || !$onlogin)) {
                // If user is not enrolled and this instance enrol on login or called with sync task, try to enrol.
                $PAGE->set_context(context_course::instance($instance->courseid));
                if ($this->user_autoenrol($instance, $user)) {
                    if ($onlogin) {
                        // Purge the associated caches for the current user only.
                        $presignupcache = \cache::make('core', 'presignup');
                        $presignupcache->purge_current_user();
                    }
                }
            } else if ($found) {
                // If user is enrolled check if the rule still verified.
                if (!$this->check_rule($instance, $user)) {
                    if (!$context = context_course::instance($instance->courseid, IGNORE_MISSING)) {
                        // Very weird.
                        continue;
                    }

                    // Deal with enrolments of users that no more match the rule.
                    $unenrolaction = $this->get_config('autounenrolaction');
                    if ($unenrolaction === false) {
                        $unenrolaction = ENROL_EXT_REMOVED_UNENROL;
                    }
                    if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                        $this->unenrol_user($instance, $user->id);

                    } else if ($unenrolaction == ENROL_EXT_REMOVED_SUSPEND || $unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                        // Suspend users.
                        foreach ($userenrolments as $userenrolment) {
                            if ($userenrolment->enrolid == $instance->id) {
                                if ($userenrolment->status != ENROL_USER_SUSPENDED) {
                                    $this->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
                                }
                            }
                        }
                        if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                            if (!empty($roleassigns[$instance->courseid])) {
                                // We want this "other user" to keep their roles.
                                continue;
                            }
                            role_unassign_all(array(
                                    'contextid' => $context->id,
                                    'userid' => $user->id,
                                    'component' => 'enrol_autoenrol',
                                    'itemid' => $instance->id
                            ));
                        }
                    }
                } else {
                    // If rule is verified update user group enrolments.
                    $this->process_group($instance, $user);
                }
            }

        }
    }

    /**
     * Forces synchronisation of all autoenrol instances for all users.
     *
     * @param progress_trace $trace
     * @param int            $course course id
     *
     * @return void
     */
    public function sync_enrolments(progress_trace $trace, $course) {
        global $DB;

        // We may need a lot of memory here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        // Get records of all active users.
        $users = $DB->get_records('user', array('deleted' => '0', 'suspended' => 0), null, '*');

        $trace->output(get_string('checksync', 'enrol_autoenrol', count($users)));
        foreach ($users as $user) {
            if (!is_siteadmin($user) && (!isguestuser($user))) {
                $this->sync_user_enrolments($user, false, $course);
            }
        }

    }

    /**
     * Process long time not seen user and expiration
     *
     * @param progress_trace $trace
     * @param int $courseid one course, empty mean all
     * @return int 0 means ok, 1 means error, 2 means plugin disabled
     */
    public function sync_expirations(progress_trace $trace, $courseid = null) {
        global $DB;

        if (!enrol_is_enabled('autoenrol')) {
            $trace->finished();
            return 2;
        }

        // Unfortunately this may take a long time, execution can be interrupted safely here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $trace->output('Verifying autoenrolments expiration...');

        $params = array('now' => time(), 'useractive' => ENROL_USER_ACTIVE, 'courselevel' => CONTEXT_COURSE);
        $coursesql = "";
        if ($courseid) {
            $coursesql = "AND e.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // First deal with users that did not log in for a really long time - they do not have user_lastaccess records.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'autoenrol' AND e.customint3 > 0)
                  JOIN {user} u ON u.id = ue.userid
                 WHERE :now - u.lastaccess > e.customint3
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint3 / DAYSECS;
            $trace->output("unenrolling user $userid from course $instance->courseid " .
                "as they did not log in for at least $days days", 1);
        }
        $rs->close();

        // Now unenrol from course user did not visit for a long time.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'autoenrol' AND e.customint3 > 0)
                  JOIN {user_lastaccess} ul ON (ul.userid = ue.userid AND ul.courseid = e.courseid)
                 WHERE :now - ul.timeaccess > e.customint3
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint3 / DAYSECS;
            $trace->output("unenrolling user $userid from course $instance->courseid " .
                "as they did not access the course for at least $days days", 1);
        }
        $rs->close();

        $trace->output('...user autoenrolment updates finished.');
        $trace->finished();

        $this->process_expirations($trace, $courseid);

        return 0;
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     *
     * @param int $courseid
     *
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/autoenrol:config', $context)) {
            return null;
        }

        // Multiple instances supported.
        return new moodle_url('/enrol/autoenrol/edit.php', array('courseid' => $courseid));
    }

    /**
     * The autoenrol plugin has several bulk operations that can be performed.
     * @param course_enrolment_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_enrolment_manager $manager) {
        $context = $manager->get_context();

        $bulkoperations = array();
        if (has_capability('enrol/autoenrol:manage', $context)) {
            $bulkoperations['editselectedusers'] = new enrol_autoenrol_editselectedusers_operation($manager, $this);
        }
        if (has_capability('enrol/autoenrol:unenrol', $context)) {
            $bulkoperations['deleteselectedusers'] = new enrol_autoenrol_deleteselectedusers_operation($manager, $this);
        }
        return $bulkoperations;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = array(
                'courseid'   => $data->courseid,
                'enrol'      => $this->get_name(),
                'status'     => $data->status,
                'roleid'     => $data->roleid,
            );
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $userid
     * @param int $oldinstancestatus
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Restore role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {
        // Just restore every role.
        if ($DB->record_exists('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid))) {
            role_assign($roleid, $userid, $contextid, 'enrol_'.$instance->enrol, $instance->id);
        }
    }

    /**
     * Restore user group membership.
     * @param stdClass $instance
     * @param int $groupid
     * @param int $userid
     */
    public function restore_group_member($instance, $groupid, $userid) {
        global $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        // This might be called when forcing restore as manual enrolments.

        groups_add_member($groupid, $userid);
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/autoenrol:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/autoenrol:config', $context);
    }

    /**
     * Intercepts the instance deletion call and gives some
     * custom instructions before resuming the parent function
     *
     * @param stdClass $instance
     */
    public function delete_instance($instance) {
        global $DB, $CFG;

        if ($this->get_config('removegroups')) {
            require_once($CFG->dirroot . '/group/lib.php');

            $groups = $DB->get_records_sql('SELECT * FROM {groups} WHERE "courseid" = :courseid AND ' .
                    $DB->sql_like('idnumber', ':idnumber'),
                    array('idnumber' => 'autoenrol|' . $instance->id . '|%', 'courseid' => $instance->courseid));

            foreach ($groups as $group) {
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
     *
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $USER, $OUTPUT;

        if (!$this->enrol_allowed($USER, $instance)) {
            return false;
        }

        if ($this->user_autoenrol($instance, $USER)) {
            return true;
        }

        $form = new enrol_form(null, $instance);
        $instanceid = optional_param('instance', 0, PARAM_INT);
        if ($instance->id == $instanceid) {
            if ($data = $form->get_data()) {
                $this->enrol_user($instance, $USER->id, $instance->roleid, time(), 0);
                $this->process_group($instance, $USER);
                // Send welcome message.
                if ($instance->customint7 != ENROL_DO_NOT_SEND_EMAIL) {
                    $this->email_welcome_message($instance, $USER);
                }
            }
        }

        return $OUTPUT->box($form->render());
    }

    /**
     * Add new instance of enrol plugin with default settings.
     *
     * @param object $course
     *
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        $fields = $this->get_instance_defaults();

        return $this->add_instance($course, $fields);
    }

    /**
     * Returns defaults for new instances.
     *
     * @return array
     */
    public function get_instance_defaults() {
        $expirynotify = $this->get_config('expirynotify');
        if ($expirynotify == 2) {
            $expirynotify = 1;
            $notifyall = 1;
        } else {
            $notifyall = 0;
        }

        $fields = array();
        $fields['roleid']          = $this->get_config('defaultrole');
        $fields['enrolperiod']     = $this->get_config('enrolperiod');
        $fields['expirynotify']    = $expirynotify;
        $fields['notifyall']       = $notifyall;
        $fields['expirythreshold'] = $this->get_config('expirythreshold');
        $fields['customint1']      = 0;
        $fields['customint3']      = $this->get_config('longtimenosee');
        $fields['customint4']      = $this->get_config('newenrols');
        $fields['customint5']      = $this->get_config('maxenrolled');
        $fields['customint6']      = 0;
        $fields['customint7']      = $this->get_config('sendcoursewelcomemessage');
        $fields['customint8']      = 0;

        return $fields;
    }

    /**
     * Check and add or remove user from/to related groups
     *
     * @param stdClass $instance
     * @param object   $user
     */
    private function process_group(stdClass $instance, $user) {
        global $CFG;

        $profileattribute = '';
        if (isset($instance->customchar3) && ($instance->customchar3 != '-')) {
            $profileattribute = $instance->customchar3;
        } else if (isset($instance->customint2)) {
            $oldfields = array(0 => '', 1 => 'auth', 2 => 'department', 3 => 'institution', 4 => 'lang', 5 => 'email');
            $profileattribute = $oldfields[$instance->customint2];
        }

        if (!empty($profileattribute)) {
            require_once($CFG->dirroot . '/group/lib.php');

            $name = '';
            if (!empty($instance->customchar1)) {
                $name = $instance->customchar1;
            } else {
                $standardfields = array('auth', 'lang', 'department', 'institution', 'address', 'city', 'email');
                if (in_array($profileattribute, $standardfields)) {
                    $name = $user->$profileattribute;
                } else {
                    require_once($CFG->dirroot.'/user/profile/lib.php');
                    $userdata = profile_user_record($user->id);
                    if (!empty($userdata) && isset($userdata->$profileattribute)) {
                        $name = $userdata->$profileattribute;
                    } else {
                        $name = get_string('emptyfield', 'enrol_autoenrol', $profileattribute);
                    }
                }
            }

            $groupid = 0;
            if (!empty($name)) {
                $groupid = $this->get_group($instance, $name);
            }

            // Check if this instance already added this user to a group and remove membership if needed.
            $usergroups = groups_get_all_groups($instance->courseid, $user->id);
            if (!empty($usergroups)) {
                foreach ($usergroups as $usergroupid => $usergroup) {
                    // Check if each group with this user as member was created by Autoenrol.
                    if (strpos($usergroup->idnumber, 'autoenrol|'.$instance->id.'|') === false) {
                        unset($usergroups[$usergroupid]);
                    }
                    // ATTENTION!! - We can't remove user membership from groups not created by Autoenrol.
                }

                if (!empty($usergroups) && (count($usergroups) == 1)) {
                    $usergroup = reset($usergroups);
                    if ($usergroup->id != $groupid) {
                        groups_remove_member($usergroup->id, $user->id);
                    }
                }
            }

            if (!empty($name)) {
                return groups_add_member($groupid, $user->id);
            } else {
                return null;
            }
        }
    }

    /**
     * Get group id named groupname if not exists create it
     *
     * @param stdClass $instance
     * @param string   $groupname
     *
     * @return int|mixed id of the group
     *
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_group(stdClass $instance, $groupname) {
        global $DB;

        // Try to get group from idnumber.
        $hash = md5($groupname);
        $idnumber = 'autoenrol|' . $instance->id . '|' .$hash;

        $group = $DB->get_record('groups', array('idnumber' => $idnumber, 'courseid' => $instance->courseid));

        if ($group == null) {
            // If not exists idnumber Try to get group from name.
            $group = $DB->get_record('groups', array('name' => $groupname, 'courseid' => $instance->courseid));
        }

        if ($group == null) {
            $newgroupdata = new stdclass();
            $newgroupdata->courseid = $instance->courseid;
            $newgroupdata->name = $groupname;
            $newgroupdata->idnumber = $idnumber;
            $newgroupdata->description = get_string('auto_desc', 'enrol_autoenrol');
            $groupid = groups_create_group($newgroupdata);
        } else {
            $groupid = $group->id;
        }

        return $groupid;
    }

    /**
     * Send welcome email to specified user.
     *
     * @param stdClass $instance
     * @param stdClass $user user record
     * @return void
     */
    protected function email_welcome_message($instance, $user) {
        global $CFG, $DB;

        $course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, array('context' => $context));
        $a->profileurl = new moodle_url($CFG->wwwroot . '/user/view.php', array('id' => $user->id, 'course' => $course->id));
        $a->link = course_get_url($course)->out();

        if (trim($instance->customtext1) !== '') {
            $message = $instance->customtext1;
            $key = array('{$a->coursename}', '{$a->profileurl}', '{$a->link}', '{$a->fullname}', '{$a->email}');
            $value = array($a->coursename, $a->profileurl, $a->link, fullname($user), $user->email);
            $message = str_replace($key, $value, $message);
            if (strpos($message, '<') === false) {
                // Plain text only.
                $messagetext = $message;
                $messagehtml = text_to_html($messagetext, null, false, true);
            } else {
                // This is most probably the tag/newline soup known as FORMAT_MOODLE.
                $messagehtml = format_text($message, FORMAT_MOODLE,
                    array('context' => $context, 'para' => false, 'newlines' => true, 'filter' => false));
                $messagetext = html_to_text($messagehtml);
            }
        } else {
            $messagetext = get_string('welcometocoursetext', 'enrol_self', $a);
            $messagehtml = text_to_html($messagetext, null, false, true);
        }

        $subject = get_string('welcometocourse', 'enrol_autoenrol',
            format_string($course->fullname, true, array('context' => $context)));

        $sendoption = $instance->customint7;
        $contact = $this->get_welcome_email_contact($sendoption, $context);

        // Directly emailing welcome message rather than using messaging.
        email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
    }

    /**
     * Get the "from" contact which the email will be sent from.
     *
     * @param int $sendoption send email from constant ENROL_SEND_EMAIL_FROM_*
     * @param object $context context where the user will be fetched
     * @return mixed|stdClass the contact user object.
     */
    public function get_welcome_email_contact($sendoption, $context) {
        global $CFG;

        if (!defined('ENROL_SEND_EMAIL_FROM_COURSE_CONTACT')) {
            define('ENROL_SEND_EMAIL_FROM_COURSE_CONTACT', 1);
        }
        if (!defined('ENROL_SEND_EMAIL_FROM_NOREPLY')) {
            define('ENROL_SEND_EMAIL_FROM_NOREPLY', 3);
        }

        $contact = null;
        // Send as the first user assigned as the course contact.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_COURSE_CONTACT) {
            $rusers = array();
            if (!empty($CFG->coursecontact)) {
                $croles = explode(',', $CFG->coursecontact);
                list($sort, $sortparams) = users_order_by_sql('u');
                // We only use the first user.
                $i = 0;
                do {
                    $rusers = get_role_users($croles[$i], $context, true, '',
                        'r.sortorder ASC, ' . $sort, null, '', '', '', '', $sortparams);
                    $i++;
                } while (empty($rusers) && !empty($croles[$i]));
            }
            if ($rusers) {
                $contact = array_values($rusers)[0];
            }
        }

        // If send welcome email option is set to no reply or if none of the previous options have
        // returned a contact send welcome message as noreplyuser.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_NOREPLY || empty($contact)) {
            $contact = core_user::get_noreply_user();
        }

        return $contact;
    }
}
