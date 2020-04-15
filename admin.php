<?php
/**
 * Functions related to general administration of this plugin: defining the
 * forum url, creating a new forum, forum administration, etc.
 */

// Perform url validation?
if (is_array($_GET)) {
  $ValidateUrl = vf_get_value('vanillavalidate', $_GET, '');
  if ($ValidateUrl != '') {
	 echo vf_validate_url($ValidateUrl);
	 die();
  }
}

// Init plugin options to white list our options
function vf_admin_init() {
  register_setting(VF_OPTIONS_NAME, VF_OPTIONS_NAME, 'vf_validate_options');

  $page = vf_get_value('page', $_GET);
  if (in_array($page, array('vf-admin-handle', 'vf-embed-handle', 'vf-widgets-handle'))) {
	 // This will add /wp-content/plugins/vanillaforums/assets/vanillaforums.js to the current page
	 wp_enqueue_script(
		'vanillaforums',
		plugins_url('/assets/vanillaforums.js', __FILE__),
		array('jquery'),
		'1.0'
	 );
  
	 // This will add /wp-content/plugins/vanillaforums/assets/vanillaforums.css to the current page
	 wp_enqueue_style(
		'vanillaforums',
		plugins_url('/assets/vanillaforums.css', __FILE__),
		array(),
		'1.2'
	 );
  }
  
  // This will add /wp-content/plugins/vanillaforums/assets/admin.css to the all admin pages
  wp_enqueue_style(
	 'vanillaforumsadmin',
	 plugins_url('/assets/admin.css', __FILE__),
	 array(),
	 '1.2'
  );
}

function vf_add_vanilla_menu() {
  add_menu_page('Vanilla Forum', 'Vanilla Forum', 'manage_options', 'vf-admin-handle', 'vf_admin_page', plugins_url('assets/transparent.png', __FILE__));
  add_submenu_page('vf-admin-handle', 'Setup', 'Setup', 'manage_options', 'vf-admin-handle', 'vf_admin_page');
  
  // Don't show the various forum pages unless the forum url has been properly defined.
  if (vf_get_option('url', '') != '') {
	 add_submenu_page('vf-admin-handle', 'Forum Integration', 'Forum Integration', 'manage_options', 'vf-embed-handle', 'vf_embed_admin_page');
	 add_submenu_page('vf-admin-handle', 'Comment Integration', 'Comment Integration', 'manage_options', 'vf-comment-handle', 'vf_comment_admin_page');
	 add_submenu_page('vf-admin-handle', 'Widgets', 'Widgets', 'manage_options', 'vf-widgets-handle', 'vf_widgets_admin_page');
	 add_submenu_page('vf-admin-handle', 'Single Sign-on', 'Single Sign-on', 'manage_options', 'vf-sso-handle', 'vf_sso_admin_page');
  }
}

function vf_admin_page() {  
  // Check that the user has the required capability 
  if (!current_user_can('manage_options'))
	 wp_die(__('You do not have sufficient permissions to access this page.'));
  
  $options = get_option(VF_OPTIONS_NAME);
  $url = vf_get_value('url', $options);
?>
<style type="text/css">
.wrap {
  font-family: 'lucida grande','Lucida Sans Unicode',Tahoma,sans-serif;
}
.Progress {
  /* display: none; */
}
.Validated,
.Invalid {
  display: none;
  padding: 6px 8px;
  font-size: 12px;
  background: #D6FFCF;
  color: #06A800;
}
.Invalid {
  background: none repeat scroll 0 0 #FFD1D1;
  color: #C90000;
}
form em {
  display: block;
}
form strong {
  font-size: 14px;
  display: block;
}
.InputBox {
  font-size: 16px;
  width: 300px;
  margin: 10px 0 3px;
}
</style>
<script type="text/javascript">
jQuery(document).ready( function($) {
	
	/* Validate a vanilla forum url */
	validateUrl = function() {
		var validateUrl = $('input.InputBox').val();
      if (validateUrl == '') {
         $('.Progress').hide();
         return;
      }
      
		$('.Validated, .Invalid').hide();
		$('.Progress').show();
		$.ajax({
		  url: '<?php echo site_url('wp-admin/admin.php?page=vf-admin-handle&vanillavalidate='); ?>'+validateUrl,
		  success: function(data) {
			 if (data.indexOf('http://') == 0 || data.indexOf('https://') == 0) {
				$('input.InputBox').val(data);
				$('.Validated').show();
			 } else {
				$('.Invalid').show();
			 }
		  },
		  error: function(data) {
			 $('.Invalid').show();
		  },
		  complete: function() {
			 $('.Progress').hide();
		  }
		});
		return false;
	}
	$('input.Validate').click(validateUrl);
	validateUrl();
});
</script>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('Vanilla Forums Setup'); ?></h2>
   <p>Use this page to configure your Vanilla Forum to work with WordPress.</p>
	<?php if ($url == '') { ?>
	 <div class="GetVanilla">
		<h2>Don't have a Vanilla Forum yet?</h2>
		<a href="http://vanillaforums.com" target="_blank"><span>Get one in under 60 seconds!</span></a>
	 </div>
	<?php
	}
	vf_open_form('url-form');
	?>
		<strong>Enter the full url of your Vanilla Forum:</strong>
		<input name="<?php echo vf_get_option_name('url'); ?>" value="<?php echo $url; ?>" class="InputBox" />
		<span class="Progress">...</span>
		<span class="Validated">Validated :)</span>
		<span class="Invalid">Couldn't find a Vanilla Forum at this url :/</span>
		<em>eg. https://yourdomain.com/forum</em>
      <p class="submit">
		  <input type="submit" class="Validate" name="validate" value="<?php _e('Re-validate'); ?>" />
		  <input type="submit" class="Save" name="save" value="<?php _e('Save'); ?>" />
		</p>
   </form>
</div>
<?php
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
			<div class="CopyBox"><?php echo wp_login_url(); ?>?redirect_to={Target}</div>
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
