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

## [Changelog](CHANGES.md)

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

Issue tracker can be found on [GitHub](https://github.com/bobopinna/moodle-enrol_autoenrol/issues). Please
try to give as much detail about your problem as possible and I'll do what I can to help.

## License

Released Under the GNU General Public Licence http://www.gnu.org/copyleft/gpl.html
