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
		global $current_user;
		if (!function_exists('get_currentuserinfo'))
			require (ABSPATH . WPINC . '/pluggable.php');
			
      get_currentuserinfo();
		require_once dirname(__FILE__).'/functions.jsconnect.php';
		
		$user = array();
		if ($current_user->ID != '') {
			$user['uniqueid'] = $current_user->ID;
			$user['name'] = $current_user->display_name;
			$user['email'] = $current_user->user_email;
			$user['photourl'] = '';
			$user['wp_nonce'] = wp_create_nonce('log-out');
		}
		
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

/**
 * Single Sign-on administration page.
 */
function vf_sso_admin_page() {
	if (!current_user_can('manage_options'))
		wp_die(__('You do not have sufficient permissions to access this page.'));
  
	$options = get_option(VF_OPTIONS_NAME);
	$sso_enabled = vf_get_value('sso-enabled', $options, '');
	$sso_clientid = vf_get_value('sso-clientid', $options, vf_format_url(get_option('blogname')));
	$sso_secret = vf_get_value('sso-secret', $options, '');
	$vanilla_url = vf_get_value('url', $options);
?>
<style type="text/css">
.wrap strong {
	display: block;
	font-size: 14px;
}
.TextBox {
	width: 300px;
}
.form-container {
	background: #f0f0f0;
	display: block;
	max-width: 800px;
	padding: 10px;
	margin: 0 0 20px;
}
.form-container label {
	display: block;
	padding: 0 0 16px;
}
.form-container label:last-child {
	padding: 0;
}
.form-container span {
	display: block;
}
.info-container {
	background: #f0f0f0;
	display: block;
	max-width: 800px;
	padding: 10px;
}
.form-container label,
.info-container label {
	cursor: auto;
}
.CopyBox {
	font-size: 12px;
	border: 1px solid #ddd;
	background: #fff;
	padding: 3px 6px;
	font-family: monospace;
	margin-bottom: 10px;
	border-radius: 2px;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
}
.important {
	font-weight: bold;
	font-style: italic;
}
</style>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.generate-secret').click(function() {
		$.ajax({
			url: $(this).attr('href'),
			success: function(data) {
				$('input.sso-secret').val(data);
			}
			
		});
		return false;
	});
});
</script>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('Vanilla Single Sign-on Integration'); ?></h2>
	<?php vf_open_form('sso-form'); ?>
	<br />
	<strong>Security Settings for Single Sign-on</strong>
	<div class="form-container">
		<label>
			<strong>Enable</strong>
			<input type="checkbox" name="<?php echo vf_get_option_name('sso-enabled'); ?>" value="1"<?php echo $sso_enabled == '1' ? ' checked="checked"' : ''; ?> />
			Allow users to sign into Vanilla through WordPress.
		</label>

		<label>
			<strong>Client ID</strong>
			<span>The client id is a url-friendly value that identifies your WordPress site to Vanilla.</span>
			<input class="TextBox" type="text" name="<?php echo vf_get_option_name('sso-clientid'); ?>" value="<?php echo $sso_clientid; ?>" />
		</label>

		<label>
			<strong>Secret</strong>
			<span>This is a "secret" value that Vanilla uses to ensure that your WordPress site is a trusted source.</span>
			<input class="TextBox sso-secret" type="text" name="<?php echo vf_get_option_name('sso-secret'); ?>" value="<?php echo $sso_secret; ?>" />
			<a class="generate-secret" href="<?php echo site_url('?VFRequest=generate-secret'); ?>">Generate</a>
		</label>
	</div>
	<strong>Other information for Vanilla</strong>
	<div class="info-container">
		<label>
			<strong>Authenticate Url</strong>
			<div class="CopyBox"><?php echo site_url('?VFRequest=connect'); ?></div>
		</label>
		<label>
			<strong>Sign In Url</strong>
			<div class="CopyBox"><?php echo wp_login_url(); ?>?redirect_to={Redirect}</div>
		</label>
		<label>
			<strong>Register Url</strong>
			<div class="CopyBox"><?php echo site_url('wp-login.php?action=register', 'login'); ?></div>
		</label>
	</div>

	<p class="important">Make sure that <u>all</u> of the values above are copied into <a href="<?php echo vf_combine_paths(array($vanilla_url, 'dashboard/settings/jsconnect')); ?>">your Vanilla jsConnect settings page</a>.</p>

   <p class="submit"><input type="submit" name="save" value="<?php _e('Save Changes'); ?>" /></p>
	</form>
</div>
<?php
/*
 May need this value in the future...
			<th>Sign-out Url</th>
			<td><span class="description"><?php
				echo add_query_arg(array('action' => 'logout', '_wpnonce' => '{Nonce}', 'redirect_to' => '{Redirect}'), site_url('wp-login.php', 'login'));
			?></span></td>
*/

}