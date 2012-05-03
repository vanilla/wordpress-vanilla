<?php if (!is_preview()) {
$options = get_option(VF_OPTIONS_NAME);

$categoryid = vf_get_value('embed-categoryid', $options, '0');

if (vf_get_value('embed-matchcategories', $options, '0')) {
   // Send the post's category ID instead of the default.
   $categories = get_the_category();
   if (!empty($categories)) {
      $category = array_shift($categories);
      if (isset($category->slug))
         $categoryid = $category->slug;
   }
}
?>
<div id="vanilla-comments"></div>
<script type="text/javascript">
var vanilla_forum_url = '<?php echo vf_get_value('url', $options); ?>'; // Required: the full http url & path to your vanilla forum
var vanilla_identifier = '<?php echo $post->ID; ?>'; // Required: your unique identifier for the content being commented on
var vanilla_url = '<?php echo get_permalink(); ?>'; // Current page's url
<?php if ($categoryid) { ?>
var vanilla_category_id = '<?php echo $categoryid; ?>'; // Embed comments in this category
<?php } ?>
<?php 
if ($sso = vf_get_sso_string()) {
   echo "var vanilla_sso = '$sso';"; // Single-Sign-On string.
}
?>
(function() {
	var vanilla = document.createElement('script');
	vanilla.type = 'text/javascript';
	var timestamp = new Date().getTime();
	vanilla.src = vanilla_forum_url + '/js/embed.js';
	(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="http://vanillaforums.com/?ref_noscript">comments powered by Vanilla.</a></noscript>
<div class="vanilla-credit"><a class="vanilla-anchor" href="http://vanillaforums.com">Comments by <span class="vanilla-logo">Vanilla</span></a></div>
<?php }