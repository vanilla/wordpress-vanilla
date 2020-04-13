<?php
require __DIR__.'/vendor/autoload.php';
use Vanilla\JsConnect;
/**
 * Single Sign-on functions.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL|E_STRICT);

/**
 * Check to see if we should kill processing and display information for Vanilla
 */
$VFRequest = vf_get_value('VFRequest', $_GET);

switch ($VFRequest) {
    // Show the signed in user
    case 'connect':
        $user = vf_get_user();
        $options = get_option(VF_OPTIONS_NAME);
        $clientID = vf_get_value('sso-clientid', $options, '');
        $secret = vf_get_value('sso-secret', $options, '');
        JsConnect\JsConnectJSONP::WriteJsConnect($user, $_GET, $clientID, $secret, true);
        exit();
        break;
    // Generate a secret to be used for security.
    case 'generate-secret':
        echo wp_generate_password(64, true, true);
        exit();
        break;
}
