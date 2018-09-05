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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Auto Enrol ';
$string['pluginname_desc'] = 'The automatic enrolment module allows an option for logged in users to be automatically granted entry to a course and enrolled. This is similar to allowing guest access but the students will be permanently enrolled and therefore able to participate in forum and activities within the area.';

$string['config'] = 'Configuration';
$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to user\'s profile page {$a->profileurl}
* User email {$a->email}
* User fullname {$a->fullname}';
$string['general'] = 'General';
$string['filtering'] = 'User Filtering';

$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'When a user is auto enrolled in the course, they may be sent a welcome message email. If sent from the course contact (by default the teacher), and more than one user has this role, the email is sent from the first user to be assigned the role.';

$string['warning'] = 'Caution!';
$string['warning_message'] = 'Adding this plugin to your course will allow any registered Moodle users access to your course. Only install this plugin if you want to allow open access to your course for users who have logged in.';
$string['welcomemessage'] = 'Welcome message';
$string['welcometocourse'] = 'Welcome to {$a}';

$string['role'] = 'Role';
$string['role_help'] = 'Power users can use this setting to change the permission level at which users are enrolled.';

$string['method'] = 'Enrol When';
$string['m_site'] = 'Logging into Site';
$string['m_course'] = 'Loading the Course';
$string['method_help'] = 'Power users can use this setting to change the plugin\'s behaviour so that users are enrolled to the course upon logging in rather than waiting for them to access the course. This is helpful for courses which should be visible on a users "my courses" list by default.';

$string['groupon'] = 'Group By';
$string['g_none'] = 'Select...';
$string['g_auth'] = 'Auth Method';
$string['g_dept'] = 'Department';
$string['g_email'] = 'Email Address';
$string['g_inst'] = 'Institution';
$string['g_lang'] = 'Language';
$string['groupon_help'] = 'AutoEnrol can automatically add users to a group when they are enrolled based upon one of these user fields.';

$string['countlimit'] = 'Limit';
$string['countlimit_help'] = 'This instance will count the number of enrolments it makes on a course and can stop enrolling users once it reaches a certain level. The default setting of 0 means unlimited.';

$string['alwaysenrol'] = 'Always Enrol';
$string['alwaysenrol_help'] = 'When set to Yes the plugins will always enrol users, even if they already have access to the course through another method.';

$string['softmatch'] = 'Soft Match';
$string['softmatch_help'] = 'When enabled AutoEnrol will enrol a user when they partially match the "Allow Only" value instead of requiring an exact match. Soft matches are also case-insensitive. The value of "Filter By" will be used for the group name.';

$string['instancename'] = 'Custom Label';
$string['instancename_help'] = 'You can add a custom label to make it clear what this enrolment method does. This option is most useful when there are multiple instances of AutoEnrol on one course.';

$string['filter'] = 'Allow Only';
$string['filter_help'] = 'When a group focus is selected you can use this field to filter which type of user you wish to enrol onto the course. For example, if you grouped by authentication and filtered with "manual" only users who have registered directly with your site would be enrolled.';

$string['auto'] = 'Auto';
$string['auto_desc'] = 'This group has been automatically created by the Auto Enrol plugin. It will be deleted if you remove the Auto Enrol plugin from the course.';

$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during automatic enrolments';
$string['autoenrol:config'] = 'Configure automatic enrolments';
$string['autoenrol:manage'] = 'Manage autoenrolled users';
$string['autoenrol:method'] = 'User can enrol users onto a course at login';
$string['autoenrol:unenrol'] = 'User can unenrol autoenrolled users';
$string['autoenrol:unenrolself'] = 'User can unenrol themselves if they are being enrolled on access';
$string['autoenrol:hideshowinstance'] = 'User can enable or disable autoenrol instances';

$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"? You can revisit the course to be reenrolled but information such as grades and assignment submissions may be lost.';

$string['emptyfield'] = 'No {$a}';

$string['removegroups'] = 'Remove groups';
$string['removegroups_desc'] = 'When an enrolment instance is deleted, should it attempt to remove the groups it has created?';

$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users will be enrolled from this date onward only.';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users will be enrolled until this date only.';
$string['cannotenrol'] = 'You can\'t enrol to this course using auto enrol.';
$string['privacy:metadata:core_group'] = 'Autoenrol plugin can create new groups or use existing groups to add participants that match the Autoenrol filter.';
