jQuery(document).ready(function ($) {

	//$('.currency').before('$');
	
	// render readonly options that no longer apply
	if ($('input[name="imag_mall_options_tech[auto_order]"]:checked').val() == 'false') {
		$('input[name*="auto_order_"]').prop('readOnly', true).css('background-color', 'LightGray');
	}
	
	$('input[name="imag_mall_options_tech[auto_order]"]').change(function () {
		if ($('input[name="imag_mall_options_tech[auto_order]"]:checked').val() == 'false') {
			$('input[name*="auto_order_"]').prop('readOnly', true).css('background-color', 'LightGray');
		}
		else {
			$('input[name*="auto_order_"]').prop('readOnly', false).css('background-color', 'white');
		}
	});
	
	if ($('input[name="imag_mall_options_tech[auto_offer]"]:checked').val() == 'false') {
		$('input[name*="auto_offer_"]').prop('readOnly', true).css('background-color', 'LightGray');
	}
	
	$('input[name="imag_mall_options_tech[auto_offer]"]').change(function () {
		if ($('input[name="imag_mall_options_tech[auto_offer]"]:checked').val() == 'false') {
			$('input[name*="auto_offer_"]').prop('readOnly', true).css('background-color', 'LightGray');
		}
		else {
			$('input[name*="auto_offer_"]').prop('readOnly', false).css('background-color', 'white');
		}
	});
	
	// override buttons
	$('button#override_order, button#override_offer, button#override_shipping').click(function () {
		var ddlID = $(this).attr('id') + '_user';
		//alert(ddlID);
		var pathParts = window.location.href.split('/');
		//pathParts[pathParts.length-1] = 'user-edit.php?user_id=' + $('select[name="'+ddlID+'"]').val() + '#auto-forward';
		pathParts[pathParts.length-1] = 'admin.php?page=forwarding&user_id=' + $('select[name="'+ddlID+'"]').val() + '#auto-forward';
		window.location = pathParts.join('/');
		return false;
	});
});