<?php
/**
 * Single Sign-on functions.
 */

/**
 * Check to see if we should kill processing and display information for Vanilla
 */
$VFRequest = vf_get_value('VFRequest', $_GET);
switch ($VFRequest) {
	// Show the signed in user
	case 'connect':
      require_once dirname(__FILE__).'/functions.jsconnect.php';
      
      $user = vf_get_user();
		$options = get_option(VF_OPTIONS_NAME);
		$clientID = vf_get_value('sso-clientid', $options, '');
		$secret = vf_get_value('sso-secret', $options, '');
		WriteJsConnect($user, $_GET, $clientID, $secret, true);
		exit();
		break;
	// Generate a secret to be used for security.
	case 'generate-secret':
		echo md5(time());
		exit();
		break;
}
