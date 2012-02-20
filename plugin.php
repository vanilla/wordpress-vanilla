<?php
/*
Plugin Name: Vanilla Forums
Plugin URI: http://vanillaforums.org/addons/
Description: Integrates Vanilla Forums with Wordpress: embedded comments, embedded forum, single sign on, and Wordpress widgets.
Version: 1.1.3
Author: Mark O'Sullivan
Author URI: http://www.vanillaforums.org/

ChangeLog:
1.0.4
- Fixed validation of Vanilla Url to correct when users incorrectly enter the path to their discussion instead of the actual root of the forum.
- Fixed a bug that caused Vanilla Admin JS & CSS to be included on all wp dashboard pages.
- Fixed a bug that caused the copy of the embed template to fail and throw a fatal PHP error.
- Added an option to the embed form that allows widgets to use the embed url instead of the actual forum url.
- Changed discussions widget to friendly-url-encode discussion titles.
- Fixed plugin to work with forums that are not using mod_rewrite.
1.0.5
- Fixed css & js includes so the containing folder is no longer hard-coded.
- Added css file so Vanilla icon displays on all admin pages (not just when looking at a vanilla page).
1.0.6
- Forgot to add the admin.css file to svn in the last release
1.1.0
- Cleaned up url validation (allow you to override it now if you want)
- Adding new jsConnect SSO functionality
- Added comment embedding
1.1.1
- Somehow missed adding some files for 1.1.0
1.1.2
- Missing css & image files
- Removed code that was causing embedded comments to get encoded/decoded poorly
1.1.3
- Fixed discussion & activity widgets to pull data correctly.
- Removed unnecessary variables from comments.php template.
- Removed unnecessary timestamping of count.js url.

Copyright 2010 Vanilla Forums Inc
This file is part of the Vanilla Forums plugin for WordPress.
The Vanilla Forums plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The Vanilla Forums plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with the Vanilla Forums plugin.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc at support [at] vanillaforums [dot] com
*/

define('VF_OPTIONS_NAME', 'vf-options');
define('VF_PLUGIN_PATH', dirname(__FILE__));
define('VF_PLUGIN_URL', WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)));

include_once(VF_PLUGIN_PATH.'/functions.php'); 
include_once(VF_PLUGIN_PATH.'/admin.php');
include_once(VF_PLUGIN_PATH.'/embed.php');
include_once(VF_PLUGIN_PATH.'/widgets.php');
include_once(VF_PLUGIN_PATH.'/sso.php');
include_once(VF_PLUGIN_PATH.'/hooks.php');
