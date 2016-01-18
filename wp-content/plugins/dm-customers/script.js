jQuery(document).ready(function ($) {

	// render readonly options that no longer apply
	$('input[name="auto_order"]:checked').each(function() {
		if ($(this).val() != 'true') {
			$('.gray-if-manual-order input').attr('readonly', 'readonly');
			$('.gray-if-manual-order select option').each(function () {
				if (!$(this).attr('selected')) {
					$(this).attr('disabled', 'disabled');
				}
			});
			$('.gray-if-manual-order').css('background-color', 'LightGray');
		}
	});
	$('input[name="auto_order"]').click(function () {
		if ($(this).val() == 'true') {
			$('.gray-if-manual-order input').removeAttr('readonly');
			$('.gray-if-manual-order select option').removeAttr('disabled');
			$('.gray-if-manual-order').css('background-color', 'white');
		}
		else {
			$('.gray-if-manual-order input').attr('readonly', 'readonly');
			$('.gray-if-manual-order select option').each(function () {
				if (!$(this).attr('selected')) {
					$(this).attr('disabled', 'disabled');
				}
			});
			$('.gray-if-manual-order').css('background-color', 'LightGray');
		}
	});
	
	$('input[name="auto_offer"]:checked').each(function () {
		if ($(this).val() != 'true') {
			$('.gray-if-manual-offer input').attr('readonly', 'readonly');
			$('.gray-if-manual-offer select option').each(function () {
				if (!$(this).attr('selected')) {
					$(this).attr('disabled', 'disabled');
				}
			});
			$('.gray-if-manual-offer').css('background-color', 'LightGray');
		}
	});
	$('input[name="auto_offer"]').click(function () {
		if ($(this).val() == 'true') {
			$('.gray-if-manual-offer input').removeAttr('readonly');
			$('.gray-if-manual-offer select option').removeAttr('disabled');
			$('.gray-if-manual-offer').css('background-color', 'white');
		}
		else {
			$('.gray-if-manual-offer input').attr('readonly', 'readonly');
			$('.gray-if-manual-offer select option').each(function () {
				if (!$(this).attr('selected')) {
					$(this).attr('disabled', 'disabled');
				}
			});
			$('.gray-if-manual-offer').css('background-color', 'LightGray');
		}
	});
	
	// override shortcuts
	$('.override-link').on('click', function () {
		var userID = $(this).closest('td').find('select').val();
		window.location = WPURLS.admin + 'user-edit.php?user_id=' + userID +'#overrides';
	});
	
	// control user settings
	$('#profile-page input[name="auto_order"], #profile-page input[name="auto_order"], #profile-page input[name="ship_to_consumer"]').on('mousedown', function (e) {
		console.log('test');
		if ($('#profile-page select[name="role"]').val() == 'rpr_unverified') {
			e.stopImmediatePropagation();
			alert('Change user role to Customer before setting overrides.');
		}
	});
	
	$('#profile-page select[name="role"]').on('change', function () {
		if ($(this).val() == 'rpr_unverified')
			$('#profile-page input[name="auto_order"], #profile-page input[name="auto_order"], #profile-page input[name="ship_to_consumer"]').filter('[value="inherit"]').click();
	});
});