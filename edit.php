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

require('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once('edit_form.php');

$courseid = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT); // The instanceid.

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('enrol/autoenrol:config', $context);

$PAGE->set_url('/enrol/autoenrol/edit.php', array('courseid' => $course->id));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id' => $course->id));
if (!enrol_is_enabled('autoenrol')) {
    redirect($return);
}

$plugin = enrol_get_plugin('autoenrol');

if ($instanceid) {
    $instance = $DB->get_record(
            'enrol', array('courseid' => $course->id, 'enrol' => 'autoenrol', 'id' => $instanceid), '*', MUST_EXIST);
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id' => $course->id)));
    $instance = new stdClass();
    $instance->id = null;
    $instance->courseid = $course->id;
}

$mform = new enrol_autoenrol_edit_form(null, array($instance, $plugin, $context));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if (!isset($data->customchar3)) {
        $data->customchar3 = '';
    }
    if ($data->customint4 != 0 && $data->customint4 != 1) {
        $data->customint4 = 0;
    }
    if ($data->customint5 < 0) {
        $data->customint5 = 0;
    }
    if ($data->customint8 != 0 && $data->customint8 != 1) {
        $data->customint8 = 0;
    }
    if (($data->customint6 != 0 && $data->customint6 != 1) || ($data->customint1 == 1)) {
        $data->customint6 = 0;
    }
    if (!isset($data->customint7)) {
        $data->customint7 = 0;
    }

    if ($instance->id) {
        $instance->timemodified = time();
        if (has_capability('enrol/autoenrol:method', $context)) {
            $instance->customint1 = $data->customint1;
            $instance->customint3 = $data->customint3;
        }
        $instance->customint4 = $data->customint4;
        $instance->customint5 = $data->customint5;
        $instance->customint6 = $data->customint6;
        $instance->customint7 = $data->customint7;
        $instance->customint8 = $data->customint8;
        $instance->customchar1 = $data->customchar1;
        $instance->customchar2 = $data->customchar2;
        $instance->customchar3 = $data->customchar3;
        $instance->customtext1 = $data->customtext1;
        $instance->enrolstartdate = $data->enrolstartdate;
        $instance->enrolenddate = $data->enrolenddate;
        $DB->update_record('enrol', $instance);

        // Do not add a new instance if one already exists (someone may have added one while we are looking at the edit form).
    } else {
        $fields = array('customint1' => 0,
                        'customint3' => 5,
                        'customint4' => $data->customint4,
                        'customint5' => $data->customint5,
                        'customint6' => $data->customint6,
                        'customint7' => $data->customint7,
                        'customint8' => $data->customint8,
                        'customchar1' => $data->customchar1,
                        'customchar2' => $data->customchar2,
                        'customchar3' => $data->customchar3,
                        'customtext1' => $data->customtext1,
                        'enrolstartdate' => $data->enrolstartdate,
                        'enrolenddate' => $data->enrolenddate
        );
        if (has_capability('enrol/autoenrol:method', $context)) {
            $fields['customint1'] = $data->customint1;
            $fields['customint3'] = $data->customint3;
        }

        $plugin->add_instance($course, $fields);

    }

    redirect($return);
}

$PAGE->set_title(get_string('pluginname', 'enrol_autoenrol'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_autoenrol'));
$mform->display();
echo $OUTPUT->footer();
