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
 * Autoenrol enrolment tests.
 *
 * @package    enrol_autoenrol
 * @category   phpunit
 * @copyright  2026 LightMoon Projects
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_autoenrol;

use context_course;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/autoenrol/lib.php');

/**
 * Testable autoenrol plugin wrapper.
 */
class testable_enrol_autoenrol_plugin extends \enrol_autoenrol_plugin {
    /** @var int */
    public $welcomecallcount = 0;

    /**
     * Preserve the production enrolment plugin name.
     *
     * @return string
     */
    public function get_name() {
        return 'autoenrol';
    }

    /**
     * Expose the protected welcome sender for tests.
     *
     * @param stdClass $instance
     * @param stdClass $user
     * @return void
     */
    public function send_welcome_message(stdClass $instance, stdClass $user): void {
        $this->email_welcome_message($instance, $user);
    }

    /**
     * Count calls while preserving the production implementation.
     *
     * @param stdClass $instance
     * @param stdClass $user
     * @return void
     */
    protected function email_welcome_message($instance, $user) {
        $this->welcomecallcount++;
        parent::email_welcome_message($instance, $user);
    }
}

/**
 * Autoenrol enrolment tests.
 */
final class lib_test extends \advanced_testcase {
    /**
     * Ensure disabled welcome messages do not submit notifications.
     */
    public function test_welcome_disabled_submits_no_message(): void {
        $this->resetAfterTest();

        $plugin = new testable_enrol_autoenrol_plugin();
        [$course, $instance, $teacher, $user] = $this->create_welcome_fixture(0);
        $sink = $this->redirectMessages();

        $result = $plugin->user_autoenrol($instance, $user);

        $this->assertTrue($result);
        $this->assertSame(0, $plugin->welcomecallcount);
        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * Ensure welcome messages are sent once through the Message API.
     */
    public function test_welcome_enabled_submits_one_message_with_expected_payload(): void {
        $this->resetAfterTest();

        $plugin = new testable_enrol_autoenrol_plugin();
        [$course, $instance, $teacher, $user] = $this->create_welcome_fixture(ENROL_SEND_EMAIL_FROM_COURSE_CONTACT);
        $context = context_course::instance($course->id);
        $courseurl = course_get_url($course)->out(false);
        $sink = $this->redirectMessages();

        $result = $plugin->user_autoenrol($instance, $user);

        $this->assertTrue($result);
        $this->assertSame(1, $plugin->welcomecallcount);

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);

        $message = reset($messages);
        $this->assertSame('enrol_autoenrol', $message->component);
        $this->assertSame('course_welcome', $message->eventtype);
        $this->assertSame((int) $teacher->id, (int) $message->useridfrom);
        $this->assertSame((int) $user->id, (int) $message->useridto);
        $this->assertSame('Welcome to Application & Eligibility Review', $message->subject);
        $this->assertSame($message->subject, $message->smallmessage);
        $this->assertSame((int) FORMAT_PLAIN, (int) $message->fullmessageformat);
        $this->assertSame(1, (int) $message->notification);
        $this->assertSame($courseurl, $message->contexturl);
        $this->assertSame(format_string($course->fullname, true, ['context' => $context]), $message->contexturlname);

        $this->assertStringContainsString(fullname($user), $message->fullmessagehtml);
        $this->assertStringContainsString(format_string($course->fullname, true, ['context' => $context]), $message->fullmessagehtml);
        $this->assertStringContainsString($courseurl, $message->fullmessagehtml);
        $this->assertStringContainsString('<div dir="rtl">خوش آمدید</div>', $message->fullmessagehtml);

        $this->assertStringContainsString(fullname($user), $message->fullmessage);
        $this->assertStringContainsString('Application & Eligibility Review', $message->fullmessage);
        $this->assertStringContainsString($courseurl, $message->fullmessage);
        $this->assertStringContainsString('خوش آمدید', $message->fullmessage);
        $this->assertDoesNotMatchRegularExpression('/<[^>]+>/', $message->fullmessage);
    }

    /**
     * Ensure the welcome sender can be exercised directly for subject edge cases.
     *
     * @param string $coursename
     * @param string $expectedsubject
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('subject_normalisation_provider')]
    public function test_subject_normalisation_cases(string $coursename, string $expectedsubject): void {
        global $DB;

        $this->resetAfterTest();

        $plugin = new testable_enrol_autoenrol_plugin();
        [$course, $instance, $teacher, $user] = $this->create_welcome_fixture(ENROL_SEND_EMAIL_FROM_COURSE_CONTACT);
        $course->fullname = $coursename;
        $DB->update_record('course', $course);
        $instance->courseid = $course->id;
        $sink = $this->redirectMessages();

        $plugin->send_welcome_message($instance, $user);

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertSame('enrol_autoenrol', $message->component);
        $this->assertSame('course_welcome', $message->eventtype);
        $this->assertSame($expectedsubject, $message->subject);
        $this->assertSame($expectedsubject, $message->smallmessage);
    }

    /**
     * Ensure the noreply sender remains supported.
     */
    public function test_welcome_message_can_use_noreply_sender(): void {
        $this->resetAfterTest();

        $plugin = new testable_enrol_autoenrol_plugin();
        [$course, $instance, $teacher, $user] = $this->create_welcome_fixture(ENROL_SEND_EMAIL_FROM_NOREPLY);
        $sink = $this->redirectMessages();

        $plugin->send_welcome_message($instance, $user);

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertSame(\core_user::get_noreply_user()->id, (int) $message->useridfrom);
        $this->assertSame((int) $user->id, (int) $message->useridto);
    }

    /**
     * Ensure the existing call sites still route through the shared welcome sender.
     */
    public function test_welcome_call_sites_route_through_shared_method(): void {
        $contents = file_get_contents(__DIR__ . '/../lib.php');

        $this->assertSame(2, preg_match_all('/->email_welcome_message\(\$instance,\s*\$[A-Z_a-z]+\)/', $contents));
        $this->assertSame(1, preg_match_all('/function email_welcome_message\(/', $contents));
        $this->assertSame(1, preg_match_all('/\$message->courseid\s*=\s*\$course->id;/', $contents));
        $this->assertSame(1, preg_match_all('/message_send\(\$message\)/', $contents));
        $this->assertSame(0, preg_match_all('/email_to_user\(\$user,\s*\$[A-Z_a-z]+,\s*\$subject,\s*\$messagetext,\s*\$messagehtml\)/', $contents));
    }

    /**
     * Subject normalisation cases.
     *
     * @return array
     */
    public static function subject_normalisation_provider(): array {
        return [
            'literal ampersand' => [
                'Application & Eligibility Review',
                'Welcome to Application & Eligibility Review',
            ],
            'encoded ampersand' => [
                'Application &amp; Eligibility Review',
                'Welcome to Application & Eligibility Review',
            ],
            'apostrophe' => [
                'Doctors\' Orientation',
                'Welcome to Doctors\' Orientation',
            ],
            'quotes' => [
                '"Application Review"',
                'Welcome to "Application Review"',
            ],
            'persian' => [
                'آمادگی و ارزیابی',
                'Welcome to آمادگی و ارزیابی',
            ],
        ];
    }

    /**
     * Create a course, instance, teacher and learner for welcome-message tests.
     *
     * @param int $sendoption
     * @return array
     */
    private function create_welcome_fixture(int $sendoption): array {
        global $CFG, $DB;

        $enabled = array_filter(
            explode(',', (string) get_config('', 'enrol_plugins_enabled'))
        );

        if (!in_array('autoenrol', $enabled, true)) {
            $enabled[] = 'autoenrol';
            set_config('enrol_plugins_enabled', implode(',', $enabled));
        }

        unset($CFG->enrol_plugins_enabled_cache);

        $generator = $this->getDataGenerator();
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);
        $CFG->coursecontact = (string) $teacherrole->id;

        $course = $generator->create_course([
            'fullname' => 'Application &amp; Eligibility Review',
            'shortname' => 'AER',
        ]);
        $teacher = $generator->create_user([
            'firstname' => 'Course',
            'lastname' => 'Teacher',
            'email' => 'teacher@example.com',
        ]);
        $user = $generator->create_user([
            'firstname' => 'Student',
            'lastname' => 'Example',
            'email' => 'student@example.com',
        ]);

        role_assign($teacherrole->id, $teacher->id, context_course::instance($course->id)->id);

        /** @var \enrol_autoenrol_plugin $plugin */
        $plugin = enrol_get_plugin('autoenrol');
        $instanceid = $plugin->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'roleid' => $studentrole->id,
            'customint1' => 0,
            'customint4' => 1,
            'customint7' => $sendoption,
            'customint8' => 0,
            'customtext1' => '<p>Hello {$a->fullname}</p><div dir="rtl">خوش آمدید</div><p><a href="{$a->link}">{$a->coursename}</a></p>',
        ]);
        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);

        return [$course, $instance, $teacher, $user];
    }
}
