<?php

// only enqueue all the admin stuff if is_admin
if (is_admin()) {
	// Initialize admin settings
	add_action('admin_init', 'vf_admin_init');

	// Add menu options to dashboard
	add_action('admin_menu', 'vf_add_vanilla_menu');
}

// Replace the page content with the vanilla embed code if viewing the page that
// is supposed to contain the forum.
add_filter('the_content', 'vf_embed_content');

// Handle saving the permalink via ajax
add_action('wp_ajax_vf_embed_edit_slug', 'vf_embed_edit_slug');

$options = get_option(VF_OPTIONS_NAME);
$url = vf_get_value('url', $options);
if ($url != '') {
	// Add Vanilla Widgets to WordPress
	add_action('widgets_init', 'vf_widgets_init');

	// Override wordpress' core functions for rendering comments and comment counts.
	add_filter('comments_template', 'vf_comments_template', 1, 2);
   
   // Place the Vanilla Forum on the external domain redirect whitelist.
   add_filter('allowed_redirect_hosts', 'vf_allowed_redirect_hosts', 10, 2 );
}

// Override the comment link html
add_filter('comments_open', 'vf_comments_open');
add_filter('comments_number', 'vf_comments_number');
// Add our js to update the comment count
add_action('wp_footer', 'vf_comment_count_js');
add_action('wp_loaded', 'vf_check_request');
//add_filter('allowed_redirect_hosts', 'vf_allowed_redirect_hosts');