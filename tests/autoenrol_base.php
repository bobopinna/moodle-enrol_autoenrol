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

defined('MOODLE_INTERNAL') || die();

require_once('testlib.php');

/**
 * Class autoenrol_base
 *
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class autoenrol_base extends PHPUnit_Framework_TestCase {
    /**
     * @param $obj
     * @param $method
     */
    protected function assert_method_exists($obj, $method) {
        if (!method_exists($obj, $method)) {
            $classname = get_class($obj);
            $this->fail("method $classname::$method does not exist");
        }
    }

    /**
     * @param int $id
     *
     * @return stdclass
     */
    protected function get_example_user($id = 1) {
        $user = new stdclass();
        $user->id = 2;
        $user->firstname = "Test";
        $user->lastname = "User " . $id;

        return $user;
    }
}
