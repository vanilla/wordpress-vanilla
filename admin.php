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
			 if (data.indexOf('http://') == 0) {
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
		<em>eg. http://yourdomain.com/forum</em>
      <p class="submit">
		  <input type="submit" class="Validate" name="validate" value="<?php _e('Re-validate'); ?>" />
		  <input type="submit" class="Save" name="save" value="<?php _e('Save'); ?>" />
		</p>
   </form>
</div>
<?php
}