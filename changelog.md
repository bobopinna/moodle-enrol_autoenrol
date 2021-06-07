# Changelog
All notable changes to this project will be documented in this file.

## [Unreleased]

## [v2.3]
### Added
- Enrolment duration.
- Unenrol inactive after.

### Changed
- Renewed user filtering, now with Moodle standard availability interface.

## [v2.2]
### Added
- Commad line script and scheduled task for batch auto enrolment.

### Changed
- Fixed just enrolled courses in Dashboard with auto enrol on login.

## [v2.1.1]
### Added
- Backup and restore support.

## [v2.1]
### Added
- Bulk operations.
- API Privacy support.
- Self unenrol.

## [v2.0]
### Added
- Custom profile fields support.
- Method delete feature.
- Auto unenrol when profile field changes and it do not match the filter.
- Group remove/change when profile field change.

### Changed
- Fixed code style to pass Moodle code checker tests.

## [v1.3.1]
### Added
- New capability to control whether user can enable or disable instances.

### Changed
- Tweaks to the Readme to improve readability and installation instructions.

## [v1.3] - Release for Moodle 2.6, 2.7 and 2.8.
### Added
- New setting option to control the group cleanup behaviour.

### Changed
- Groups now identified by group idnumber instead of name (so feel free to rename groups!).

## [v1.2]
### Added
- It is now possible to add multiple instances to a single course.
- An option to give instance a custom label.
- An option to limit number of enrolments. 
- A permission for users to unenrol themselves if not enrolling during login.

### Changed
- Filtering functions now allow for partial matches.
- Expanded filtering functions to include email address.
- By default, users are now only enrolled if they aren't already enrolled on a course.
- Individual users can now be manually unenrolled through Users > Enrolled Users.

## [v1.1] - Minor update
### Changed
- Improved instance configuration form compatibility with Moodle 2.5.

## [v1.0] - Stable release
### Added
- New config option to "Add instance to new courses".

## [v0.91]
### Changed
- Bug Fix - Filtering was being bypassed when enrolling on site-login.

## [v0.9] - Beta release
### Added
- Initial commit
