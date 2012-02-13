jQuery(document).ready( function($) {
	/* JS for ajax-editing of embed url permalink */
	editPermalink = function(post_id) {
		var i, c = 0, e = $('#editable-post-name'), revert_e = e.html(), real_slug = $('#post_name'), revert_slug = real_slug.val(), b = $('#edit-slug-buttons'), revert_b = b.html(), full = $('#editable-post-name-full').html();
	
		$('#view-post-btn').hide();
		b.html('<a href="#" class="save button">Save</a> <a class="cancel" href="#">Cancel</a>');
		b.children('.save').click(function() {
			var new_slug = e.children('input').val();
			$.post(ajaxurl, {
				action: 'vf_embed_edit_slug',
				post_id: post_id,
				new_slug: new_slug,
				new_title: $('#title').val(),
				samplepermalinknonce: $('#samplepermalinknonce').val()
			}, function(data) {
				$('#edit-slug-box').html(data);
				b.html(revert_b);
				real_slug.attr('value', new_slug);
				makeSlugeditClickable();
				$('#view-post-btn').show();
			});
			return false;
		});
	
		$('.cancel', '#edit-slug-buttons').click(function() {
			$('#view-post-btn').show();
			e.html(revert_e);
			b.html(revert_b);
			real_slug.attr('value', revert_slug);
			return false;
		});
	
		for ( i = 0; i < full.length; ++i ) {
			if ( '%' == full.charAt(i) )
				c++;
		}
	
		slug_value = ( c > full.length / 4 ) ? '' : full;
		e.html('<input type="text" id="new-post-slug" value="'+slug_value+'" />').children('input').keypress(function(e){
			var key = e.keyCode || 0;
			// on enter, just save the new slug, don't save the post
			if ( 13 == key ) {
				b.children('.save').click();
				return false;
			}
			if ( 27 == key ) {
				b.children('.cancel').click();
				return false;
			}
			real_slug.attr('value', this.value);
		}).focus();
	}
	
	makeSlugeditClickable = function() {
		$('#editable-post-name').click(function() {
			$('#edit-slug-buttons').children('.edit-slug').click();
		});
	}
	makeSlugeditClickable();
	
});
