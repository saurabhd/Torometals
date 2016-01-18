function reEnable () {
	var $ = jQuery;
	$('.spinner').hide();
	$('#publish').removeClass('button-primary-disabled');
}

function buildTable() {
	var $ = jQuery;
	var productType = $('#product-type').val();
	var originalProductType = $('#product-type').data('originalType');
	
	if (!$('#vol-breaks').length && !$('#vol-breaks-sell').length)
		return true;
		
	if (productType == 'fiztrade' && $('#_product_id').val() == '')
		return true;
	
	$('#volume_breaks_data .fiztrade, #volume_breaks_data .dealer').hide();
	$('#volume_breaks_data .'+productType).show();
	
	// breaks and breakType defined in volume-breaks.php
	if (typeof breakType == 'undefined')
		breakType = 'EA';
	if (breaks.length == 0)
		return;
		
		
	if (breakType == 'EA')
		$('#vol-breaks-units').prop('checked', true);
	else
		$('#vol-breaks-ounces').prop('checked', true);
	
	$('#vol-breaks, #vol-breaks-sell, #vol-breaks-buy').empty();
	
	// build volume breaks table
	if (productType == 'fiztrade') {
		$('#vol-breaks-sell').appendGrid({
			caption: '',
			columns: [
				{ name: 'dg_ask', display: 'DG Ask', type: 'text', ctrlProp: { disabled: true }, ctrlClass: 'price' },
				{ name: 'units', display: breakType == 'EA' ? 'Units (low - high)' : 'Ounces (low - high)', type: 'text', ctrlProp: { readonly: true } },
				{ name: 'units_high', display: breakType == 'EA' ? 'Units' : 'Ounces', type: 'text', ctrlProp: { disabled: true } },
				{ name: 'percent_ask', display: '% Premium', type: 'text', ctrlProp: { readonly: true } },
				{ name: 'flat_ask', display: 'Flat Premium', type: 'text', ctrlProp: { readonly: true } },
				{ name: 'retail_ask', display: 'Retail Price', type: 'text', ctrlProp: { disabled: true }, ctrlClass: 'price' }
			],
			initData: breaks // breaks defined on page
		});
		$('#vol-breaks-buy').appendGrid({
			caption: '',
			columns: [
				{ name: 'dg_bid', display: 'DG Bid', type: 'text', ctrlProp: { disabled: true }, ctrlClass: 'price' },
				{ name: 'units', display: breakType == 'EA' ? 'Units (low - high)' : 'Ounces (low - high)', type: 'text', ctrlProp: { readonly: true } },
				{ name: 'units_high', display: breakType == 'EA' ? 'Units' : 'Ounces', type: 'text', ctrlProp: { disabled: true } },
				{ name: 'percent_bid', display: '% Premium', type: 'text', ctrlProp: { readonly: true } },
				{ name: 'flat_bid', display: 'Flat Premium', type: 'text', ctrlProp: { readonly: true } },
				{ name: 'retail_bid', display: 'Retail Price', type: 'text', ctrlProp: { disabled: true }, ctrlClass: 'price' }
			],
			initData: breaks // breaks defined on page
		});
				
		// can't change rows on fiztrade item - hide the buttons
		$('#vol-breaks-sell td:last-child, #vol-breaks-buy td:last-child').hide();
				
		$('#vol-breaks-sell thead td:eq(3), #vol-breaks-buy thead td:eq(3)').remove();
		$('#vol-breaks-sell thead td:eq(2), #vol-breaks-buy thead td:eq(2)').attr('colspan', 2);
		
	}
	else {
		var weightLabel = $('#weight_unit').val() == 'oz' ? 'Ounces' : 'Grams';
		
		$('#vol-breaks').appendGrid({
			caption: '',
			columns: [
				{ name: 'units', display: breakType == 'EA' ? 'Range of Units' : 'Range of '+weightLabel, headerSpan: 2, type: 'number' },
				{ name: 'units_high', display: breakType == 'EA' ? 'Units' : 'Ounces', type: 'text', ctrlProp: { disabled: true } },
				{ name: 'percent_ask', display: '% Premium', type: 'number' },
				{ name: 'flat_ask', display: 'Flat Premium', type: 'number' }
			],
			initData: breaks, // breaks defined on page
			afterRowRemoved: function (caller, rowIndex) {
				setHighUnits();
			}
		}).find('input[type="number"]:not([name*="units"])').attr('step', 'any');
		
		
		setHighUnits();
		//$('#vol-breaks .delete').on('click', setHighUnits);
		$('#vol-breaks .moveUp, #vol-breaks .moveDown, #vol-breaks .insert').remove();
		
		$('#vol-breaks thead td:eq(2)').remove();
		$('#vol-breaks thead td:eq(1)').attr('colspan', 2);
	
		// row 1 is linked to price input on General tab, units can't be altered on this page
		$('#vol-breaks_Row_1 input').prop('readonly', true);
		//$('[id$="units"]').prop('readonly', false);
		
		// if (breaks) {
			// $('#_sell_flat_premium, #_sell_price').val(breaks[0]['flat_ask']);
			// $('#_sell_percent_premium, #_sell_spot_premium').val(breaks[0]['percent_ask']);
		// }
		
		// clear and hide appropriate columns for premium type
		if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent') {
			$('#vol-breaks thead tr td:nth-child(4)').hide();
			$('#vol-breaks thead tr td:nth-child(3)').show();
			$('#vol-breaks tbody tr td:nth-child(5)').hide().find('input').val('');
			$('#vol-breaks tbody tr td:nth-child(4)').show();
			$('#vol-breaks tfoot td').attr('colspan', 5);
		} 
		else {
			$('#vol-breaks thead tr td:nth-child(3)').hide();
			$('#vol-breaks thead tr td:nth-child(4)').show();
			$('#vol-breaks tbody tr td:nth-child(4)').hide().find('input').val('');
			$('#vol-breaks tbody tr td:nth-child(5)').show();
			$('#vol-breaks tfoot td').attr('colspan', 5);
		}
	}
	
	$('.price').formatCurrency();
	
	if (typeof fixedBid != 'undefined') {
		$('#_buy_flat_premium').val(fixedBid);
		$('#_buy_percent_premium').val(percentBid);
	}
	
	// hack to handle first units box somehow getting cleared
	$('#vol-breaks_units_1').val(1);
	
	// keep first row from being deleted	
	$('#vol-breaks tbody tr:first-child .delete').off('click').on('click', function (e) {
		alert('May not remove this row.');
		e.preventDefault();
	});
	
	// validate on page submit
	$('input[type="submit"]').unbind('click').click(function (e) {
					
		// Validate
		var valid = true;
		
		// validate General tab premiums entered, if necessary
		if ($('#product-type').val() == 'dealer') {
			var sellOpt = $('#woocommerce-product-data input[name="sell_option"]:checked').val();
			var buyOpt = $('#woocommerce-product-data input[name="buy_option"]:checked').val();
			if (sellOpt == 'yes' || sellOpt == 'callA') {
				// sell premium required
				var test1 = $('#_sell_price').val();
				var test2 = $('#_sell_spot_premium').val();
				if ($('#_sell_price').val() == '' && $('#_sell_spot_premium').val() == '') {
					valid = false;
					alert('Please enter a premium if you wish to sell this product.');
				} 
				else if (parseFloat($('#_sell_price').val()) == 0 || parseFloat($('#_sell_spot_premium').val()) == 0) {
					valid = confirm('Are you sure you want to use a sell premium of 0?');
				}
			}
			if (buyOpt == 'yes' || buyOpt == 'callA') {
				// buy premium required
				if ($('#_buy_price').val() == '' && $('#_buy_spot_premium').val() == '') {
					valid = false;
					alert('Please enter a premium if you wish to purchase this product from customers.');
				} 
				else if (parseFloat($('#_buy_price').val()) == 0 || parseFloat($('#_buy_spot_premium').val()) == 0) {
					valid = confirm('Are you sure you want to use a buy premium of 0?');
				}
			}
		}
		
		if ($('#vol-breaks-ounces').is(':checked') && $('#_product_weight') == '') {
			valid = false;
			alert('Can\'t set volume breaks by weight without weight entered.');
		}
		
		var unitsCheck = [];
		$('#vol-breaks tbody tr:not(:first-child)').each(function() {
			if (!valid)
				return; // don't bother if any earlier input is invalid
			
			var rowStr = $(this).attr('id');			
			var splits = rowStr.split('_');
			var rowID = splits.pop();
			
			if (!$('#vol-breaks_units_'+rowID).val() && !$('#vol-breaks_percent_ask_'+rowID).val() && !$('#vol-breaks_flat_ask_'+rowID).val()) {
				// empty row is valid
			}
			else {
				var units = $('#vol-breaks_units_'+rowID).val();
				unitsCheck.push(units);
				
				var percentPremium = $('#vol-breaks_percent_ask_'+rowID).val();
				var flatPremium = $('#vol-breaks_flat_ask_'+rowID).val();			
				
				if (percentPremium == '' && flatPremium == '') {
					
					if (productType == 'fiztrade') {
						// copy from previous row
						$('#vol-breaks_percent_ask_'+rowID).val($('#vol-breaks_percent_ask_'+ (parseInt(rowID) - 1)).val());
						$('#vol-breaks_flat_ask_'+rowID).val($('#vol-breaks_flat_ask_'+ (parseInt(rowID) - 1)).val());
					}
					else {
						$('.spinner').hide();
						alert('Please enter a percent or flat premium (or both) on row ' + rowID + ' of the Volume Breaks table.');
						valid = false;
					}
					
				}			
			}
		});
		// check that tiers don't overlap
		for (var i=1; i<unitsCheck.length; i++) {
			if (parseInt(unitsCheck[i]) <= parseInt(unitsCheck[i-1])) {
				alert('Please ensure ranges on the Volume Breaks table don\'t overlap.');
				valid = false;
			}
		}
			
			
		if (originalProductType == 'dealer' && productType == 'fiztrade') {
			// warn that converting from dealer product to fiztrade product loses breaks
			if (!confirm('Converting from My Item to FizTrade item will erase your Volume Breaks.  Do you wish to continue?'))
				valid = false;
			else
				$('#vol-breaks').destroy();
		}
		
		if (!valid) {
			e.preventDefault();
			
			// fix spinner and button after Wordpress JS set them to processing state
			window.setTimeout(reEnable, 1);
			
			return false;
		}
		
		//alert('Here is the serialized data!!\n' + formJSON);
		return true;
	});
}

function setHighUnits () {
	var $ = jQuery;
	$('#vol-breaks [name*="units_high"]').each(function () {	
		var thisRow = $(this).closest('tr');
		$(this).closest('tr').next();
		if (thisRow.next().length) { // don't do this on last row
			var nextLow = thisRow.next().find('[name*="units_"]:not([name*="units_high"])').val();
			if (nextLow != '' && parseInt(nextLow) != NaN)
				$(this).val(parseInt(nextLow) - 1);
		}
	})		
		.last().val($('<div />').html('&infin;').text());
}

jQuery($).ready(function ($) {
	// delegate handler performs these action s on new rows
	$('#vol-breaks').on('click', 'tfoot .ui-button, .insert', function () {
		$('#vol-breaks input[id^=vol-breaks_percent_ask_], #vol-breaks input[id^=vol-breaks_flat_ask_]').attr('step', 'any');
		if ($('#product-type').val() == 'dealer') {
			if ($('input[name="_price_option"]:checked').val() == 'flat') {
				$('#vol-breaks input[id^=vol-breaks_percent_ask_]').parent().hide();
			}
			else {
				if ($('input[name="_premium_type"]:checked').val() == 'flat')
					$('#vol-breaks input[id^=vol-breaks_percent_ask_]').parent().hide();
				else
					$('#vol-breaks input[id^=vol-breaks_flat_ask_]').parent().hide();
			}
			$('#vol-breaks [name*="units"]').on('keyup click', setHighUnits);
			$('#vol-breaks .moveUp, #vol-breaks .moveDown, #vol-breaks .insert').remove();
		}
	});

	buildTable();
	
	$('#product-type').data('originalType', $(this).val());
	$('#product-type').on('change', buildTable);
	
	$('#product-type').on('change', function () {
		if ($(this).val() == 'fiztrade')
			$('.volume_breaks_tab').hide();
		else
			$('.volume_breaks_tab').show();
	});
	
	$('#volume_breaks_data input[name="_break_type"], #weight_unit').on('change', function () {		
		if ($('#volume_breaks_data input[name="_break_type"]:checked').val() == 'EA') {
			$('#vol-breaks thead td:eq(1)').text('Range of Units');
		}
		else {			
			$('#vol-breaks thead td:eq(1)').text($('#weight_unit').val() == 'oz' ? 'Range of Ounces' : 'Range of Grams');
		}
	});
	
	$('#_sell_flat_premium, #_sell_price').on('change', function () {
		$('#vol-breaks_flat_ask_1').val($(this).val());
	});
	$('#_sell_percent_premium, #_sell_spot_premium').on('change', function () {
		$('#vol-breaks_percent_ask_1').val($(this).val());
	});
	
	$('input[name="_price_option"], #product-type').on('change', function () {
		if ($('#product-type').val() == 'fiztrade')
			$('#vol-breaks input[id$="units"]').prop('readonly', true);
		else
			$('#vol-breaks input[id$="units"]').prop('readonly', false);
			
		if ($('#product-type').val() == 'dealer' && $('input[name="_price_option"]:checked').val() == 'flat') {
				$('#vol-breaks thead tr td:nth-child(3)').hide();
				$('#vol-breaks thead tr td:nth-child(4)').show().text('Price/Unit');
				$('#vol-breaks tbody tr td:nth-child(4)').hide().find('input').val('');
				$('#vol-breaks tbody tr td:nth-child(5)').show();
				$('#vol-breaks tfoot td').attr('colspan', 5);		
		}
		else {
			// clear and hide appropriate columns for premium type
			if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent') {
				$('#vol-breaks thead tr td:nth-child(4)').hide();
				$('#vol-breaks thead tr td:nth-child(3)').show();
				$('#vol-breaks tbody tr td:nth-child(5)').hide().find('input').val('');
				$('#vol-breaks tbody tr td:nth-child(4)').show();
				$('#vol-breaks tfoot td').attr('colspan', 5);
			} 
			else {
				$('#vol-breaks thead tr td:nth-child(3)').hide();
				$('#vol-breaks thead tr td:nth-child(4)').show().text('Flat Premium');
				$('#vol-breaks tbody tr td:nth-child(4)').hide().find('input').val('');
				$('#vol-breaks tbody tr td:nth-child(5)').show();
				$('#vol-breaks tfoot td').attr('colspan', 5);	
			}
		}
	}).change();
	
	$('.update-tiers-btn').on('click', function (e) {
		e.preventDefault();
		$('#vol-breaks, #vol-breaks-sell, #vol-breaks-buy').html('<tr><td class="ajax-loading9">Loading...</td></tr>');
		
		var productCode = $('#_product_id').val();
		
		if (productCode == '')
			return;
			
		$.ajax({
			url: ajaxurl,
			type: "GET",
			data: { 'action':'get_vol_breaks', 'post_id':productCode  },
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			success: function(response) {
				breaks = response.breaks;
				breakType = response.break_type;
				buildTable();
			}
		});
	});
});

function getParameterByName(name) {
    var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}