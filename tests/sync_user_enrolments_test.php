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

/**
 * Autoenrol enrolment plugin tests.
 *
 * @package    enrol_autoenrol
 * @category   phpunit
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('autoenrol_base.php');

/**
 * Class sync_user_enrolments_test
 *
 * @package    enrol_autoenrol
 * @category   phpunit
 * @copyright  2013 Mark Ward & Matthew Cannings - based on code by Martin Dougiamas, Petr Skoda, Eugene Venter and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_user_enrolments_test extends autoenrol_base {

    /**
     * Test constructor 
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     *
     * @return object
     */
    public function __construct($name = null, array $data = array(), $dataName = '') {
        require(dirname(__DIR__) . '/lib.php');
        return parent::__construct($name, $data, $dataName);
    }

    /**
     *
     * @test
     */
    public function no_records_test() {
        global $DB;
        $DB = $this->getMockForAbstractClass('moodle_database');

        $DB->expects($this->any())->method('get_records')->will($this->returnValue(array()));

        $enrol = new enrol_autoenrol_plugin();

        $user = $this->get_example_user(2);

        $this->assertMethodExists($enrol, 'sync_user_enrolments');

        $enrol->sync_user_enrolments($user);

    }
}
