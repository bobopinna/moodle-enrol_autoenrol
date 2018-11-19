# AutoEnrol Enrolment Method [![Build Status](https://travis-ci.org/bobopinna/moodle-enrol_autoenrol.svg?branch=master)](https://travis-ci.org/bobopinna/moodle-enrol_autoenrol)

When added to a course this enrolment plugin can enrol users onto a course automatically,
either as they log into your Moodle site or as they click on the course. It is intended 
for use on site-wide courses such as "Moodle Help" or "Learner Voice". 

In addition the plugin has advanced functionality to support automatically grouping and
filtering users based upon their attributes. Depending on how your user accounts are set
up this may help you to give access to certain user groups.

The plugin adds new permissions to help you manage the use of the plugin. The
first permission called "config" allows the user to add a new instance of the plugin
and change only the most basic options. The second permission is called "method" and gives
users the ability to change when the user will be enrolled on a course, and what type of 
enrolment it will be (ie something other than student). There are further permissions 
relating to the plugin which should be reviewed through Moodle's "Define Roles" page.

## Changelog
* v2.1
  * Added bulk operations
  * Added API Privacy support
  * Added self unenrol
* v2.0
  * Added custom profile fields support
  * Added method delete feature
  * Added auto unenrol when profile field changes and it do not match the filter
  * Added group remove/change when profile field change
  * Fixed code style to pass Moodle code checker tests
* v1.3.1
  * Tweaks to the Readme to improve readability and installation instructions.
  * Added new capability to control whether user can enable or disable instances.
* v1.3
  * Release for Moodle 2.6, 2.7 and 2.8.
  * Groups now identified by group idnumber instead of name (so feel free to rename groups!).
  * New setting option to control the group cleanup behaviour.
* v1.2
  * New functionality and tweaks:
  * It is now possible to add multiple instances to a single course.
  * Added an option to give instance a custom label.
  * Filtering functions now allow for partial matches.
  * Expanded filtering functions to include email address.
  * Added an option to limit number of enrolments. 
  * By default, users are now only enrolled if they aren't already enrolled on a course.
  * Individual users can now be manually unenrolled through Users > Enrolled Users.
  * Added a permission for users to unenrol themselves if not enrolling during login.
* v1.1
  * Minor update. Improved instance configuration form compatibility with Moodle 2.5.
* v1.0
  * Stable release. New config option to "Add instance to new courses".
* v0.91
  * Bug Fix - Filtering was being bypassed when enrolling on site-login.
* v0.9
  * Beta release.

## Install

1. Copy the plugin directory "autoenrol" into moodle\enrol\. 
2. Check admin notifications to install.
3. Visit the "Site Administration > Plugins > Enrolments" page.
4. Click the eye symbol next to "Auto Enrol" to enable the plugin. 

## Maintainer

The module was authored by Mark Ward and is being maintained by Roberto Pinna and Angelo Calò.

The original module developed by Mark Ward can be found on [his GitHub](https://github.com/markward/enrol_autoenrol). 

## Thanks to

With thanks to various friends for contributing, especially Matthew Cannings. 

Thanks also to users who have taken the time to share feedback and improve the module.

## Technical Support

Issue tracker can be found on [GitHub](https://github.com/bobopinna/enrol_autoenrol/issues). Please
try to give as much detail about your problem as possible and I'll do what I can to help.

## License

Released Under the GNU General Public Licence http://www.gnu.org/copyleft/gpl.html
