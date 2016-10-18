<?php
/**
 * Embed Vanilla Functions
 */

/**
 * Embed Vanilla administration page.
 */
function vf_embed_admin_page() {
  // Check that the user has the required capability
  if (!current_user_can('manage_options'))
     wp_die(__('You do not have sufficient permissions to access this page.'));

  $post_id = vf_configure_embed_container();
  $options = get_option(VF_OPTIONS_NAME);
  $embed_code = vf_get_value('embed-code', $options);
  $embed_widgets = vf_get_value('embed-widgets', $options);
  $vanilla_url = vf_get_value('url', $options);
  $vanilla_post = get_post($PostID);
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
  $('.reset-embed-code').click(function() {
     $('#EmbedCode').val('<script type="text/javascript" src="<?php echo vf_combine_paths(array($vanilla_url, 'js/embed.js'), '/'); ?>"></scrip'+'t>');
     return false;
  });
});
</script>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('Forum Integration'); ?></h2>
   <p>Use this page to embed your entire Vanilla Forum into a WordPress page.</p>
    <?php vf_open_form('embed-form'); ?>
        <strong>Forum Page in WordPress</strong>
        <em>Define where to access your Vanilla Forum within WordPress.</em>
        <div id="edit-slug-box"><?php echo get_sample_permalink_html($post_id); ?></div>
        <?php wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false ); ?>
        <em>You can further customize the page that contains your forum <a href="./post.php?post=<?php echo $post_id; ?>&action=edit">here</a>.</em>

        <strong>Widget Integration</strong>
        <p>
            <label>
                <input type="checkbox" name="<?php echo vf_get_option_name('embed-widgets'); ?>" value="1"<?php echo $embed_widgets == '1' ? ' checked="checked"' : ''; ?> />
                Force links in your <a href="./admin.php?page=vf-widgets-handle">widgets</a> to go to your forum page in WordPress (defined above) instead of going to the actual forum url (<?php echo $vanilla_url; ?>).
            </label>
        </p>

        <strong>Forum &lt;Embed&gt; Code</strong>
        <textarea id="EmbedCode" name="<?php echo vf_get_option_name('embed-code'); ?>"><?php echo $embed_code; ?></textarea>
        <em>You can make changes to your forum embed code here (optional). <a class="reset-embed-code" href="#">Reset embed code</a>.</em>
      <p class="submit"><input type="submit" name="save" value="<?php _e('Save Changes'); ?>" /></p>
        </div>
   </form>
</div>
<?php
}

/**
 * Create a page for embedding the forum, give it a default name, url, and template.
 */
function vf_configure_embed_container() {
    $post_id = vf_get_option('embed-post-id');
    $post = get_post($post_id);
    // PostID not set or not related to an existing page? Generate the page now and apply template.
    if (!is_numeric($post_id) || $post_id <= 0 || !$post) {
        $post_id = wp_insert_post(array('post_name' => 'discussions', 'post_title' => 'Discussions', 'post_type' => 'page', 'post_status' => 'publish', 'comment_status' => 'closed'));
        vf_update_option('embed-post-id', $post_id);
    }
    // Copy the vanilla template to the current theme
    $template_to_use = 'embed_template.php';
    try {
      $filepath = __DIR__.'/templates/'.$template_to_use;
      if (file_exists($filepath))
         copy($filepath, get_template_directory().'/'.$template_to_use);
        else
            $template_to_use = false;
    } catch (Exception $e) {
        $template_to_use = false;
    }
    if ($template_to_use)
        update_post_meta($post_id, '_wp_page_template', $template_to_use);

    return $post_id;
}

/**
 * Replace the page content with the vanilla embed code if viewing the page that
 * is supposed to contain the forum.
 *
 * @param string $content The content of the current page.
 */
function vf_embed_content($content) {
    global $post;
    if ($post->ID == vf_get_option('embed-post-id')) {
      $content = '';

      if ($sso = vf_get_sso_string()) {
         // Add the sso string for automatic signin.
         $content = "<script type='text/javascript'>var vanilla_sso = '$sso';</script>";
      }

        $content .= stripslashes(vf_get_option('embed-code'));

      vf_forum_embedded(true);
   }
    return $content;
}

/**
 * Handle saving the permalink via ajax.
 */
function vf_embed_edit_slug() {
    $post_id = vf_configure_embed_container();
    check_ajax_referer('samplepermalink', 'samplepermalinknonce');
    $slug = isset($_POST['new_slug'])? $_POST['new_slug'] : null;
    wp_update_post(array('ID' => $post_id, 'post_name' => $slug));
    die(get_sample_permalink_html($post_id, 'Discussion Forum', $slug));
}


/** ----=====---- EMBED COMMENTS ----=====---- **/

/**
 * Vanilla Comments administration page.
 */
function vf_comment_admin_page() {
    // Check that the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $options = get_option(VF_OPTIONS_NAME);
    $embed_comments = vf_get_value('embed-comments', $options);

    $resturl = vf_get_value('url', $options, '');
    $resturl = vf_combine_paths(array($resturl, '/categories/all.json'), '/');
    $categoryid = vf_get_value('embed-categoryid', $options, '0');
    $matchCategories = vf_get_value('embed-matchcategories', $options, '0');
    $category_data = json_decode(vf_rest($resturl), true);
    $select_options = vf_get_select_option('No Category', '0', $categoryid);
    if (is_array($category_data)) {
        if (isset($category_data['Categories'])) {
            $categories = $category_data['Categories'];
        } else {
            $categories = vf_flatten_category_tree($category_data['CategoryTree']);
        }
        foreach ($categories as $Category) {
            $select_options .= vf_get_select_option($Category['Name'], $Category['CategoryID'], $categoryid);
        }
    }

?>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('Vanilla Comment Integration'); ?></h2>
    <?php vf_open_form('embed-comments-form'); ?>
     <p>
        <label>
          <input type="checkbox" name="<?php echo vf_get_option_name('embed-comments'); ?>" value="1"<?php echo $embed_comments == '1' ? ' checked="checked"' : ''; ?> />
          Replace your blog's native commenting system with Vanilla comments.
        </label>
     </p>
     <p>
        <label>
          <select name="<?php echo vf_get_option_name('embed-categoryid'); ?>"><?php echo $select_options; ?></select>
          <?php echo __('Place embedded comments into the selected category.'); ?>
        </label>
     </p>
    <p>
       <label>
          <input type="checkbox" name="<?php echo vf_get_option_name('embed-matchcategories'); ?>" value="1"<?php echo $matchCategories == '1' ? ' checked="checked"' : ''; ?> />
          <?php echo __('Try and match your Wordpress category.'); ?>
       </label>
    </p>
    <p class="submit"><input type="submit" name="save" value="<?php _e('Save Changes'); ?>" /></p>
  </form>
</div>
<?php
}

// Include the javascript for comment counts on the main blog index
function vf_comment_count_js() {
    if (!is_admin()) {
        $options = get_option(VF_OPTIONS_NAME);
        $vanilla_url = vf_get_value('url', $options, '');
        ?>
        <script type="text/javascript">
        var vanilla_forum_url = '<?php echo $vanilla_url; ?>';
        (function() {
            var vanilla_count = document.createElement('script');
            vanilla_count.type = 'text/javascript';
            vanilla_count.src = vanilla_forum_url + '/js/count.js';
            (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla_count);
        })();
        </script>
    <?php
    }
}

// ugly global hack for comments closing
$EMBED = false;
function vf_comments_template($value) {
    global $EMBED;
    global $post;

    if (!(is_singular() && (have_comments() || $post->comment_status == 'open'))) {
        return;
    }

    if (vf_forum_embedded()) {
        $EMBED = true;
        return dirname(__FILE__).'/empty.php';
    }

     $options = get_option(VF_OPTIONS_NAME);
     $embed_comments = vf_get_value('embed-comments', $options);
     if (!$embed_comments) {
         return $value;
     }

    $EMBED = true;
    return dirname(__FILE__) . '/comments.php';
}

/**
 * Hide the default comment form by marking all comments
 * as closed.
 */
function vf_comments_open($open, $post_id = null) {
    global $EMBED;
    if ($EMBED) {
        return false;
    }
    return $open;
}

function vf_comments_number($text) {
    global $post;
    return '<span vanilla-identifier="'.$post->ID.'">'.$text.'</span>';
}
