function doItAll() {
	// Digital Metals CTA
	// Image
	var mediaLibReferer;
	
	jQuery('.add-image').click(function() {
		//tb_show(caption, url, imageGroup)
		tb_show('Upload a CTA Image', 'media-upload.php?referer=widgets.php&amp;type=image&amp;TB_iframe=true', false);
		mediaLibReferer = jQuery(this).parent().parent();
		return false;
	});
	
	window.send_to_editor = function(html) {
		// html returns a link like this:
		// <a href="{server_uploaded_image_url}"><img src="{server_uploaded_image_url}" alt="" title="" width="" height"" class="alignzone size-full wp-image-125" /></a>
		var image_url = jQuery('img',html).attr('src');
		
		//alert(html);
		mediaLibReferer.find('.image_url').val(image_url);
		tb_remove();
		mediaLibReferer.find('.has-image img').attr('src',image_url);
		mediaLibReferer.find('.no-image').hide();
		mediaLibReferer.find('.has-image').show();
		//jQuery('#submit_options_form').trigger('click');
		// jQuery('#uploaded_logo').val('uploaded');
	}
	
	jQuery('.remove-image').click(function() {
		jQuery(this).parent().parent().find('.image_url').val('');
		jQuery(this).parent().parent().find('.has-image').hide();
		jQuery(this).parent().parent().find('.no-image').show();
		return false;
	});
	
	// Link to:
	jQuery('.link-to input').each(function () {
		//alert(jQuery(this).val());
		if (jQuery(this).val() == 'category') {
			if (jQuery(this).is(':checked')) {
				jQuery(this).parent().parent().find('.sel-cat').show();
			} else {
				jQuery(this).parent().parent().find('.sel-cat').hide();			
			}
			jQuery(this).click(function () { //swap on click
				jQuery(this).parent().parent().find('.sel-url').hide();
				jQuery(this).parent().parent().find('.sel-cat').show();
			});
		}
		if (jQuery(this).val() == 'url') {
			if (jQuery(this).is(':checked')) {
				jQuery(this).parent().parent().find('.sel-url').show();
			} else {
				jQuery(this).parent().parent().find('.sel-url').hide();			
			}
			jQuery(this).click(function () { //swap on click
				jQuery(this).parent().parent().find('.sel-cat').hide();
				jQuery(this).parent().parent().find('.sel-url').show();
			});
		}
	});
}

// make sure widget displays correctly on page load and after the submit button is clicked
jQuery(document).ready(doItAll);
jQuery(document).ajaxComplete(doItAll);