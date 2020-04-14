<?php
/**
 * Utility & hook functions.
 */

function vf_check_request() {
   if (isset($_GET['VFRequest'])) {
      require dirname(__FILE__).'/sso.php';
   }
}


/**
 * Retrieve an option value from wordpress.
 *
 * @param string $option_name The name of the option to retrieve.
 * @param string $default_value The default value to return if $option_name is not defined.
 */
function vf_get_option($option_name, $default_value = FALSE) {
	$vf_options = get_option(VF_OPTIONS_NAME);
	return vf_get_value($option_name, $vf_options, $default_value);
}

function vf_get_option_name($option_name) {
	return VF_OPTIONS_NAME.'['.$option_name.']';
}

/**
 * Saves an option value to wordpress.
 *
 * @param string $option_name The name of the option to save.
 * @param string $option_value The value to save.
 */
function vf_update_option($option_name, $option_value) {
	$options = get_option(VF_OPTIONS_NAME);
	if (!is_array($options))
		$options = array();

	$options[$option_name] = $option_value;
	$return = update_option(VF_OPTIONS_NAME, $options);
}

/**
 * Return the value from an associative array or an object.
 *
 * @param string $Key The key or property name of the value.
 * @param mixed $Collection The array or object to search.
 * @param mixed $Default The value to return if the key does not exist.
 */
function vf_get_value($Key, &$Collection, $Default = FALSE) {
	$Result = $Default;
	if(is_array($Collection) && array_key_exists($Key, $Collection)) {
		$Result = $Collection[$Key];
	} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
		$Result = $Collection->$Key;
	}

	return $Result;
}

/**
 * Returns the result of a REST request to $Url.
 *
 * @param string $Url The url to make a REST request to.
 */
function vf_rest($Url) {
	$Response = wp_remote_get($Url);
	if (is_wp_error($Response))
		return $Response->get_error_message();

	return wp_remote_retrieve_body($Response);
}

/**
 * Takes an array of path parts and concatenates them using the specified
 * delimiter. Delimiters will not be duplicated. Example: all of the
 * following arrays will generate the path "/path/to/vanilla/applications/dashboard"
 * array('/path/to/vanilla', 'applications/dashboard')
 * array('/path/to/vanilla/', '/applications/dashboard')
 * array('/path', 'to', 'vanilla', 'applications', 'dashboard')
 * array('/path/', '/to/', '/vanilla/', '/applications/', '/dashboard')
 *
 * @param array $paths The array of paths to concatenate.
 * @param string $delimiter The delimiter to use when concatenating. Defaults to system-defined directory separator.
 * @returns The concatentated path.
 */
function vf_combine_paths($paths, $delimiter = '/') {
	if (is_array($paths)) {
		$munged_path = implode($delimiter, $paths);
		$munged_path = str_replace(array($delimiter.$delimiter.$delimiter, $delimiter.$delimiter), array($delimiter, $delimiter), $munged_path);
		return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $munged_path);
	} else {
		return $paths;
	}
}

/**
 * Whether or not the forum has been embedded.
 * @param bool|null $value A new value to set.
 * @return bool
 */
function vf_forum_embedded($value = null) {
   static $embedded = false;
   if ($value !== null)
      $embedded = $value;
   return $embedded;
}

/**
 * Writes out the opening of an options form.
 */
function vf_open_form($formname) {
	echo '<form method="post" action="options.php">';
	echo '<input type="hidden" name="'.vf_get_option_name('form-name').'" value="'.$formname.'" />';
	settings_fields(VF_OPTIONS_NAME);
	settings_errors();
}
function vf_close_form() {
	echo '</form>';
}

/**
 * Validates options being saved for Vanilla Forums. WordPress is a bit hinky
 * here, so we use hidden inputs to identify the forum being saved and validate
 * the inputs accordingly. This is a catch-all validation for all forms.
 */
function vf_validate_options($options) {
	$formname = vf_get_value('form-name', $options);
	$alloptions = get_option(VF_OPTIONS_NAME);
	if (!is_array($alloptions))
		$alloptions = array();

	switch ($formname) {
		case 'url-form':
			$url = vf_get_value('url', $options, '');
			$options = $alloptions;
			$options['url'] = $url;
			if (vf_get_value('embed-code', $options, '') == '') {
				// Set the embed_code if it is not already defined.
				$embedurl = vf_combine_paths(array($url, 'js/embed.js'), '/');
				$options['embed-code'] = '<script type="text/javascript" src="'.$embedurl.'"></script>';
			}
			vf_configure_embed_container();
			break;
		case 'embed-form':
			$embed_code = vf_get_value('embed-code', $options, '');
			$embed_widgets = vf_get_value('embed-widgets', $options, '0');
			$options = $alloptions;
			$url = vf_get_value('url', $options, '');
			if ($embed_code == '') {
				// Set the embed_code if it is not already defined.
				$embedurl = vf_combine_paths(array($url, 'js/embed.js'), '/');
				$options['embed-code'] = '<script type="text/javascript" src="'.$embedurl.'"></script>';
			} else {
				$options['embed-code'] = $embed_code;
			}
			$options['embed-widgets'] = $embed_widgets;
			break;
		case 'embed-comments-form':
			$embed_comments = vf_get_value('embed-comments', $options, '0');
			$embed_categoryid = vf_get_value('embed-categoryid', $options, '0');
         $matchCategories = vf_get_value('embed-matchcategories', $options, '0');
			$options = $alloptions;
			$options['embed-comments'] = $embed_comments;
			$options['embed-categoryid'] = $embed_categoryid;
         $options['embed-matchcategories'] = $matchCategories;
			break;
		default:
			$options = array_merge($alloptions, $options);
			break;
	}

	return $options;
}

/**
 * Validate that the provided url is a vanilla forum root. Returns properly formatted url if it is, or FALSE.
 */
function vf_validate_url($url) {
  $html = vf_rest($url);
  $formats = array(
  	 '"WebRoot": "',     // 2.2 // BUGFIX
  	 '"WebRoot":"',     // 2.2
  	 '\'WebRoot\' : "', // 2.0.18.13+ and 2.1.1+
  	 'WebRoot" value="' // legacy
  );

  foreach ($formats as $format) {
  	 if ($chars = strpos($html, $format)) {
  	   $offset = $chars + strlen(stripslashes($format));
    	return vf_parse_webroot($html, $offset);
    }
  }

  return FALSE;
}

/**
 * Parse URL at start of HTML.
 *
 * @param string $html
 * @param int $start
 * @return string URL.
 */
function vf_parse_webroot($html, $start) {
  $webroot = substr($html, $start);
  $webroot = substr($webroot, 0, strpos($webroot, '"'));
  $webroot = stripslashes($webroot);
  return $webroot;
}

function vf_get_select_option($name, $value, $selected_value = '') {
	return '<option value="'.$value.'"'.($value == $selected_value ? ' selected="selected"' : '').'>'.$name.'</option>';
}


/**
 * The ActivityType table has some special sprintf search/replace values in the
 * FullHeadline and ProfileHeadline fields. The ProfileHeadline field is to be
 * used on this page (the user profile page). The FullHeadline field is to be
 * used on the main activity page. The replacement definitions are as follows:
 *  %1$s = ActivityName
 *  %2$s = ActivityName Possessive
 *  %3$s = RegardingName
 *  %4$s = RegardingName Possessive
 *  %5$s = Link to RegardingName's Wall
 *  %6$s = his/her
 *  %7$s = he/she
 *  %8$s = route & routecode
 *  %9$s = gender suffix (some languages require this).
 *
 * @param object $Activity An object representation of the activity being formatted.
 * @param string $Url The root url to the forum.
 * @return string
 */
function vf_format_activity($Activity, $Url) {
   if (isset($Activity->Headline))
      return $Activity->Headline;

	$ProfileUserID = -1;
	$ViewingUserID = -1;
	$GenderSuffixCode = 'First';
	$GenderSuffixGender = $Activity->ActivityGender;

	if ($ViewingUserID == $Activity->ActivityUserID) {
		$ActivityName = $ActivityNameP = 'You';
	} else {
		$ActivityName = $Activity->ActivityName;
		$ActivityNameP = vf_format_possessive($ActivityName);
		$GenderSuffixCode = 'Third';
	}
	if ($ProfileUserID != $Activity->ActivityUserID) {
		// If we're not looking at the activity user's profile, link the name
		$ActivityNameD = urlencode($Activity->ActivityName);
		$ActivityName = vf_anchor($ActivityName, '/profile/' . $Activity->ActivityUserID . '/' . $ActivityNameD, $Url);
		$ActivityNameP = vf_anchor($ActivityNameP, '/profile/' . $Activity->ActivityUserID  . '/' . $ActivityNameD, $Url);
		$GenderSuffixCode = 'Third';
	}
	$Gender = $Activity->ActivityGender == 'm' ? 'his' : 'her';
	$Gender2 = $Activity->ActivityGender == 'm' ? 'he' : 'she';
	if ($ViewingUserID == $Activity->RegardingUserID || ($Activity->RegardingUserID == '' && $Activity->ActivityUserID == $ViewingUserID)) {
		$Gender = $Gender2 = 'your';
	}

	$IsYou = FALSE;
	$RegardingName = $Activity->RegardingName == '' ? 'somebody' : $Activity->RegardingName;
	$RegardingNameP = vf_format_possessive($RegardingName);

	if ($Activity->ActivityUserID != $ViewingUserID)
		$GenderSuffixCode = 'Third';

	$RegardingWall = '';

	if ($Activity->ActivityUserID == $Activity->RegardingUserID) {
		// If the activityuser and regardinguser are the same, use the $Gender Ref as the RegardingName
		$RegardingName = $RegardingProfile = $Gender;
		$RegardingNameP = $RegardingProfileP = $Gender;
	} else if ($Activity->RegardingUserID > 0 && $ProfileUserID != $Activity->RegardingUserID) {
		// If there is a regarding user and we're not looking at his/her profile, link the name.
		$RegardingNameD = urlencode($Activity->RegardingName);
		if (!$IsYou) {
			$RegardingName = vf_anchor($RegardingName, '/profile/' . $Activity->RegardingUserID . '/' . $RegardingNameD, $Url);
			$RegardingNameP = vf_anchor($RegardingNameP, '/profile/' . $Activity->RegardingUserID . '/' . $RegardingNameD, $Url);
			$GenderSuffixCode = 'Third';
			$GenderSuffixGender = $Activity->RegardingGender;
		}
		$RegardingWall = vf_anchor('wall', '/profile/activity/' . $Activity->RegardingUserID . '/' . $RegardingNameD . '#Activity_' . $Activity->ActivityID, $Url);
	}
	if ($RegardingWall == '')
		$RegardingWall = 'wall';

	if ($Activity->Route == '') {
		if ($Activity->RouteCode)
			$Route = $Activity->RouteCode;
		else
			$Route = '';
	} else
		$Route = vf_anchor($Activity->RouteCode, $Activity->Route, $Url);

	// Translate the gender suffix.
	$GenderSuffixCode = "GenderSuffix.$GenderSuffixCode.$GenderSuffixGender";
	$GenderSuffix = $GenderSuffixCode;
	if ($GenderSuffix == $GenderSuffixCode)
		$GenderSuffix = ''; // in case translate doesn't support empty strings.

	$FullHeadline = $Activity->FullHeadline;
	$ProfileHeadline = $Activity->ProfileHeadline;
	$MessageFormat = ($ProfileUserID == $Activity->ActivityUserID || $ProfileUserID == '' ? $FullHeadline : $ProfileHeadline);

	return sprintf($MessageFormat, $ActivityName, $ActivityNameP, $RegardingName, $RegardingNameP, $RegardingWall, $Gender, $Gender2, $Route, $GenderSuffix);
}

function vf_anchor($text, $destination, $url = '') {
	$prefix = substr($destination, 0, 7);
	if (!in_array($prefix, array('https:/', 'http://', 'mailto:'))) {
		$url = $url == '' ? vf_get_option('url') : $url;
		$destination = vf_combine_paths(array($url, $destination), '/');
	}

	return '<a href="'.$destination.'">'.$text.'</a>';
}

function vf_format_possessive($word) {
   return substr($word, -1) == 's' ? $word."'" : $word."'s";
}

function vf_get_link_url($options) {
	$embed_widgets = vf_get_value('embed-widgets', $options);
	if ($embed_widgets == '1') {
		$post_id = vf_get_option('embed-post-id');
		$post = get_post($post_id);
		$url = $post->post_name.'#';
	} else {
		$url = vf_get_value('url', $options, '');
	}
	return $url;
}

function vf_get_user() {
   global $current_user, $wp_roles;

   if (function_exists('wp_get_current_user')) {
       wp_get_current_user();
   } else {
       if (!function_exists('get_currentuserinfo')) {
           require_once ABSPATH . WPINC . '/pluggable.php';
       }
       get_currentuserinfo();
   }

   $user = array();
   if ($current_user->ID != '') {
      $user['uniqueid'] = $current_user->ID;
      $user['name'] = $current_user->display_name;
      $user['email'] = $current_user->user_email;
      $user['photourl'] = ''; //
      $user['wp_nonce'] = wp_create_nonce('log-out');

      $avatarUrl = get_avatar_url($current_user->ID);
      if ($avatarUrl) {
         $user['photourl'] = $avatarUrl;
      }

      // Add the user's roles to the SSO.
      if (isset($current_user->roles)) {
         if (!isset( $wp_roles ) )
            $wp_roles = new WP_Roles();

         $role_names = $wp_roles->role_names;

         $roles = array();
         foreach ((array)$current_user->roles as $role_slug) {
            // Add the role slug.
            $roles[] = $role_slug;

            // Add the role name if it's different from the slug.
            if (isset($role_names[$role_slug])) {
               $role_name = $role_names[$role_slug];
               if (strcasecmp($role_name, $role_slug) !== 0) {
                  $roles[] = str_replace(',', ' ', $role_name);
               }
            }
         }

         $user['roles'] = implode(',', array_unique($roles));
      } else {
         $user['roles'] = null;
      }
//      $user['_user'] = $current_user;
   }

   // Allow other plugins to modify the user.
   $user = apply_filters('vf_get_user', $user);

   return $user;
}

function vf_get_sso_string() {
   $user = vf_get_user();

   if (empty($user))
      return '';

   $options = get_option(VF_OPTIONS_NAME);
   $clientID = vf_get_value('sso-clientid', $options, '');
   $secret = vf_get_value('sso-secret', $options, '');
   if (!$clientID || !$secret)
      return '';

   $user['client_id'] = $clientID;

   $string = base64_encode(json_encode($user));
   $timestamp = time();
   $hash = hash_hmac('sha1', "$string $timestamp", $secret);

   $result = "$string $hash $timestamp hmacsha1";
   return $result;
}

function vf_user_photo($User, $Url, $CssClass = '') {
	if ($User->Photo == '')
		$User->Photo = vf_combine_paths(array($Url, 'applications/dashboard/design/images/usericon.gif'), '/');

	$CssClass = $CssClass == '' ? '' : ' class="'.$CssClass.'"';
	$IsFullPath = strtolower(substr($User->Photo, 0, 7)) == 'http://' || strtolower(substr($User->Photo, 0, 8)) == 'https://';
	$PhotoUrl = ($IsFullPath) ? $User->Photo : vf_combine_paths(array($Url, 'uploads/'.vf_change_basename($User->Photo, 'n%s')), '/');
	return '<a href="'.vf_combine_paths(array($Url, '/profile/'.$User->UserID.'/'.urlencode($User->Name)), '/').'"'.$CssClass.' style="display: inline-block; margin: 0 2px 2px 0">'
		.'<img src="'.$PhotoUrl.'" alt="'.urlencode($User->Name).'" style="width: '.$User->IconWidth.'px; height: '.$User->IconWidth.'px; overflow: hidden; display: inline-block;" />'
		.'</a>';
}

/** Change the basename part of a filename for a given path.
 *
 * @param string $Path The path to alter.
 * @param string $NewBasename The new basename. A %s will be replaced by the old basename.
 * @return string
 */
function vf_change_basename($Path, $NewBasename) {
	$NewBasename = str_replace('%s', '$2', $NewBasename);
	$Result = preg_replace('/^(.*\/)?(.*?)(\.[^.]+)$/', '$1'.$NewBasename.'$3', $Path);
	return $Result;
}

/**
 * Replaces all non-url-friendly characters with dashes.
 *
 * @param mixed $Mixed An object, array, or string to be formatted.
 * @return mixed
 */
function vf_format_url($string) {
	$string = strip_tags(html_entity_decode($string, ENT_COMPAT, 'UTF-8'));
	$string = preg_replace('`([^\PP.\-_])`u', '', $string); // get rid of punctuation
	$string = preg_replace('`([^\PS+])`u', '', $string); // get rid of symbols
	$string = preg_replace('`[\s\-/+]+`u', '-', $string); // replace certain characters with dashes
	$string = rawurlencode(strtolower($string));
	return $string;
}

/**
 * Place the Vanilla Forum on the external domain redirect whitelist.
 */
function vf_allowed_redirect_hosts($allowed_hosts, $lp) {
   $path = str_replace(array('http://', 'https://'), array('', ''), vf_get_option('url'));
   $ix = strpos($path, '/');
   if ($ix !== FALSE)
      $path = substr($path, 0, $ix);

   if (!in_array($path, $allowed_hosts))
		$allowed_hosts[] = $path;

	return $allowed_hosts;
}

function vf_flatten_category_tree($categoryTree) {
    $recursiveFlattenner = function(array $category, array &$result) use (&$recursiveFlattenner) {
        $result[] = $category;
        if (empty($category['Children'])) {
            return;
        }
        foreach ($category['Children'] as $child) {
            $recursiveFlattenner($child, $result);
        }
    };

    $result = [];

    foreach ($categoryTree as $category) {
        $recursiveFlattenner($category, $result);
    }
    return $result;
}
