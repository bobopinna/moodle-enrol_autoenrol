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
 * Class to handle welcomemessage.
 *
 * @package     enrol_autoenrol
 * @copyright   2023 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 namespace enrol_autoenrol;

use dml_exception;
use coding_exception;
use HTMLPurifier_Exception;

/**
 * Class to handle welcomemessage.
 *
 * @package     enrol_autoenrol
 * @copyright   2023 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class welcomemessage {

    /**
     * Get the welcomemessage.
     *
     * @param object $instance
     * @return array ["messagetext", [message context]]
     * @throws dml_exception
     * @throws coding_exception
     * @throws HTMLPurifier_Exception
     */
    public static function get_welcomemessage(object $instance): array {
        global $USER, $DB, $CFG;

        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        $a = new \stdClass();
        $a->coursename = format_string($course->fullname, true, ['context' => $context]);
        $a->profileurl = new \moodle_url($CFG->wwwroot . '/user/view.php', ['id' => $USER->id, 'course' => $course->id]);
        $a->link = course_get_url($course)->out();

        $message = $instance->customtext1;

        $key = ['{$a->coursename}', '{$a->profileurl}', '{$a->link}', '{$a->fullname}', '{$a->email}'];
        $value = [$a->coursename, $a->profileurl, $a->link, fullname($USER), $USER->email];

        return [str_replace($key, $value, $message), $a];
    }
}
