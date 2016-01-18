jQuery(document).ready(function($) {
/*
	function showRetailSellCalc() {
		var percentage = $('#_sell_percent_premium').val().trim() == '' ? 0.0 : parseFloat($('#_sell_percent_premium').val());
		var flat = $('#_sell_flat_premium').val().trim() == '' ? 0.0 : parseFloat($('#_sell_flat_premium').val());
		var retailPrice = parseFloat($('#ask').val()) * percentage/100 + parseFloat($('#ask').val()) + flat;
		
		var percentStr = percentage == 0 ? '' : ' + (' + $('#ask').val() + ' * ' + percentage + '%)';
		var flatStr = $('#_sell_flat_premium').val().trim() == '' ? '' : ' + ' + $('#_sell_flat_premium').val();
		var leftSideStr = $('#ask').val() + percentStr + flatStr;
		return isNaN(retailPrice) || leftSideStr == retailPrice ? 'Sell price not available' : leftSideStr + ' = <span class="result">' + retailPrice + '</span>';
	}

	function showRetailBuyCalc() {
		if (!$('#_buy_percent_premium').length)
			return;
		var percentage = $('#_buy_percent_premium').val().trim() == '' ? 0.0 : parseFloat($('#_buy_percent_premium').val());
		var flat = $('#_buy_flat_premium').val().trim() == '' ? 0.0 : parseFloat($('#_buy_flat_premium').val());
		var retailPrice = parseFloat($('#bid').val()) * percentage/100 + parseFloat($('#bid').val()) + flat;
		
		var percentStr = percentage == 0 ? '' : ' + ' + $('#bid').val() + ' * ' + percentage + '%';
		var flatStr = $('#_buy_flat_premium').val().trim() == '' ? '' : ' + ' + $('#_buy_flat_premium').val();
		var leftSideStr = $('#bid').val() + percentStr + flatStr;
		return isNaN(retailPrice) || leftSideStr == retailPrice ? 'Buy price not available' : leftSideStr + ' = <span class="result">' + retailPrice + '</span>';
	}
	*/
	function showSpotRetailSellCalc() {
		if (!$('#_sell_spot_premium').length)
			return;
		var percentage = $('#_sell_spot_premium').val().trim() == '' ? 0.0 : parseFloat($('#_sell_spot_premium').val());
		var flat = $('#_sell_price').val().trim() == '' ? 0.0 : parseFloat($('#_sell_price').val());
		var fracSplits = $('#_product_weight').val().split('/');
		if (fracSplits.length > 1) {
			var weight = parseFloat(fracSplits[0]) / parseFloat(fracSplits[1]);  // calculate fraction value
		}
		else {
			var weight = parseFloat(fracSplits[0]);
		}
		// ensure weight is in oz
		if ($('#weight_unit').val() == 'g')
			weight = weight / 31.1033;  // 31.1033 grams in a troy ounce
		
		if ($('#_calc_method').val() == 'a') {
			var retailPrice = (parseFloat($('#spot-ask').val()) * percentage/100 + parseFloat($('#spot-ask').val()) + flat) * weight;
			
			var weightStr = weight.toString();
			var s = weightStr.split('.');
			if (s.length > 1 && s[1].length > 5)
				weightStr = ' * ' + weight.toFixed(5) + 'oz ';
			else
				weightStr = ' * ' + weight + 'oz ';
			
			var percentStr = percentage == 0 ? '' : ' + ' + $('#spot-ask').val() + ' * ' + percentage + '%';
			var flatStr = $('#_sell_price').val().trim() == '' ? '' : ' + ' + $('#_sell_price').val();
			
			
			var leftSideStr;
			if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent')
				leftSideStr = '(' + $('#spot-ask').val() + percentStr + ')' + weightStr;
			else
				leftSideStr = '(' + $('#spot-ask').val() + flatStr + ')' + weightStr;
			
			return isNaN(retailPrice) || leftSideStr == retailPrice ? 'Sell price not available' : leftSideStr + ' = <span class="result">' + retailPrice + '</span>';
		
		}
		else {
			var retailPrice = retailPrice = parseFloat($('#spot-ask').val()) * weight * percentage/100 + parseFloat($('#spot-ask').val()) * weight + flat;
			
			var weightStr = weight.toString();
			var s = weightStr.split('.');
			if (s.length > 1 && s[1].length > 5)
				weightStr = ' * ' + weight.toFixed(5) + 'oz ';
			else
				weightStr = ' * ' + weight + 'oz ';
			
			var percentStr = percentage == 0 ? '' : '(' + $('#spot-ask').val() + weightStr + ' * ' + percentage + '%)';
			var flatStr = $('#_sell_price').val().trim() == '' ? '' : ' + ' + $('#_sell_price').val();
			
			
			var leftSideStr;
			if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent')
				leftSideStr = percentStr + ' + ' + $('#spot-ask').val() + weightStr;
			else
				leftSideStr = $('#spot-ask').val() + weightStr + flatStr;
			
			return isNaN(retailPrice) || leftSideStr == retailPrice ? 'Sell price not available' : leftSideStr + ' = <span class="result">' + retailPrice + '</span>';
		}
	}

	function showSpotRetailBuyCalc() {
		if (!$('#_buy_spot_premium').length)
			return;
		var percentage = $('#_buy_spot_premium').val().trim() == '' ? 0.0 : parseFloat($('#_buy_spot_premium').val());
		var flat = $('#_buy_price').val().trim() == '' ? 0.0 : parseFloat($('#_buy_price').val());
		var fracSplits = $('#_product_weight').val().split('/');
		if (fracSplits.length > 1) {
			var weight = parseFloat(fracSplits[0]) / parseFloat(fracSplits[1]);  // calculate fraction value
		}
		else {
			var weight = parseFloat(fracSplits[0]);
		}
		// ensure weight is in oz
		if ($('#weight_unit').val() == 'g')
			weight = weight / 31.1033;  // 31.1033 grams in a troy ounce
		
		if ($('#_calc_method').val() == 'a') {
			var retailPrice = (parseFloat($('#spot-bid').val()) * percentage/100 + parseFloat($('#spot-bid').val()) + flat) * weight;
			
			var weightStr = weight.toString();
			var s = weightStr.split('.');
			if (s.length > 1 && s[1].length > 5)
				weightStr = ' * ' + weight.toFixed(5) + 'oz ';
			else
				weightStr = ' * ' + weight + 'oz ';
			
			var percentStr = percentage == 0 ? '' : ' + ' + $('#spot-bid').val() + ' * ' + percentage + '%';
			var flatStr = $('#_buy_price').val().trim() == '' ? '' : ' + ' + $('#_buy_price').val();
			
			
			var leftSideStr;
			if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent')
				leftSideStr = '(' + $('#spot-bid').val() + percentStr + ')' + weightStr;
			else
				leftSideStr = '(' + $('#spot-bid').val() + flatStr + ')' + weightStr;
			
			return isNaN(retailPrice) || leftSideStr == retailPrice ? 'buy price not available' : leftSideStr + ' = <span class="result">' + retailPrice + '</span>';
		
		}
		else {
			var retailPrice = retailPrice = parseFloat($('#spot-bid').val()) * weight * percentage/100 + parseFloat($('#spot-bid').val()) * weight + flat;
			
			var weightStr = weight.toString();
			var s = weightStr.split('.');
			if (s.length > 1 && s[1].length > 5)
				weightStr = ' * ' + weight.toFixed(5) + 'oz ';
			else
				weightStr = ' * ' + weight + 'oz ';
			
			var percentStr = percentage == 0 ? '' : '(' + $('#spot-bid').val() + weightStr + ' * ' + percentage + '%)';
			var flatStr = $('#_buy_price').val().trim() == '' ? '' : ' + ' + $('#_buy_price').val();
			
			
			var leftSideStr;
			if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent')
				leftSideStr = percentStr + ' + ' + $('#spot-bid').val() + weightStr;
			else
				leftSideStr = $('#spot-bid').val() + weightStr + flatStr;
			
			return isNaN(retailPrice) || leftSideStr == retailPrice ? 'Buy price not available' : leftSideStr + ' = <span class="result">' + retailPrice + '</span>';
		}
	}
	
	// AJAX calls to get the prices for the selected product
	function updateBidAskFields() {
		$.ajax({
			url: ajaxurl,
			type: "GET",
			data: { 'action':'dg_prices', 'productID':$('#_product_id').val()  },
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			success: function(response) {
				//alert(response);
				if (response == '') {
					$('#price-error').show(); 
				}
				else {
					$('#price-error').hide();
					$('#bid').val(response['bid']);
					//$('#buy-price').html(showRetailBuyCalc());
					$('#ask').val(response['ask']);
					//$('#sell-price').html(showRetailSellCalc());
					$('.result').formatCurrency();
				}
			}
		});
	}
	
	function updateBidField() {
		$.ajax({
			url: ajaxurl,
			type: "GET",
			data: { 'action':'dg_bid', 'productID':$('#_product_id').val()  },
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			success: function(response) {
				if (response == '') {
					$('#price-error').show(); 
				}
				else {
					$('#price-error').hide();
					$('#bid').val(response);
					$('#buy-price').html(showRetailBuyCalc());
					$('.result').formatCurrency();
				}
			}
		});
	}
	
	function updateAskField() {
		$.ajax({
			url: ajaxurl,
			type: "GET",
			data: { 'action':'dg_ask', 'productID':$('#_product_id').val()  },
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			success: function(response) {
				if (response == '') {
					$('#price-error').show(); 
				}
				else {
					$('#price-error').hide();
					$('#ask').val(response);
					$('#sell-price').html(showRetailSellCalc());
					$('.result').formatCurrency();
				}
			}
		});
	}
	
	function updateSpots(metal) {
		$.ajax({
			type: "GET",
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			//url: '/data/ticker.json',
			//url: SERVICE_URL + '/FizServices/GetSpotPriceData/' + INT_TOKEN,
			data: { 'action':'ticker' },
			url: ajaxurl,
			success: function (response) {
				$('#spot-ask').val(response[metal +'Ask']);
				$('#spot-bid').val(response[metal +'Bid']);
				$('#spot-sell-price').html(showSpotRetailSellCalc());
				$('#spot-buy-price').html(showSpotRetailBuyCalc());
				$('.result').formatCurrency();
			}
		});
	}
	
	function getKG (weightStr) {
		var weight;

		if (weightStr == '' || $.isNumeric(weightStr)) {
			weight = weightStr; // no unit - assume KG
		}
		else {
			var splits = weightStr.split(' ');
			if (splits[1].toLowerCase().indexOf('kilo') !== -1) {
				weight = splits[0];
			}
			else if (splits[1].toLowerCase().indexOf('milli') !== -1) {
				weight = parseFloat(splits[0]) / 1000000;
			}
			else if (splits[1].toLowerCase().indexOf('gram') !== -1) {
				weight = parseFloat(splits[0]) / 1000;
			}
			else if (splits[1].toLowerCase().indexOf('oz') !== -1) {
				var fracSplits = splits[0].split('/');
				if (fracSplits.length > 1) {
					var decimal = parseFloat(fracSplits[0]) / parseFloat(fracSplits[1]);  // calculate fraction value
					weight = decimal / 35.274;
				}
				else {
					weight = parseFloat(splits[0]) / 35.274;
				}
			}
		}
		return weight;
	}
	
	// sets the value of an existing WooCommerce attribute named attrName 
	// the visible argument controls whether the attribute is visible on the product page
	// tested with WooCommerce 2.0.8
	function wcSetAttribute(attrName, value, visible) {
		visible = typeof visible !== 'undefined' ? visible : false;
	
		var attrClass = 'pa_' + attrName;
		var attrDiv = $('.woocommerce_attributes div.' + attrClass);
		
		attrDiv.show();
		attrDiv.find('.woocommerce_attribute_data input[type="text"]').val(value);
		if (visible) {
			attrDiv.find('.woocommerce_attribute_data input[type="checkbox"]').prop('checked', true);
		}
	}
	
	/**************************************************
						Main
	**************************************************/
	
	
	
	// show inventory tab
	$(window).load(function () {
		$('.inventory_tab').show();
		$('#inventory_product_data .show_if_simple').show();
		if (!$('#_manage_stock').is(':checked')) {
			$('.stock_fields').hide();
		}
	});
	
	
	$('#product-type').change(function () {
		$('#ft-inv-note').hide();
		$('.show_if_fiztrade, .show_if_dealer').hide();
		if ($(this).val() == 'dealer') {
			$('.show_if_dealer').show();
		} else if ($(this).val() == 'fiztrade') {
			$('.show_if_fiztrade').show();
			$('#ft-inv-note').show();
		}
	}).change();
	
	// set the default for the Allow Backorders option to Allow
	if($('#product-type').val() == 'fiztrade' &&
		$('#_product_id').val() == '') {
		$('#_backorders').val('yes');
	}
	
	// allowing user to switch retail price calculations
	$('._premium_type_field input[type="radio"]').on('change', function () {
		if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent') {
			$('#_calc_method option[value="a"]').text('A: (spot + spot * premium) * fine weight');
			$('#_calc_method option[value="b"]').text('B: (spot * fine weight * premium) + spot * fine weight');
			
			// clear and hide appropriate fields
			$('#_sell_price').val('').parent().hide();
			$('#_sell_spot_premium').parent().show();
			$('#_buy_price').val('').parent().hide();
			$('#_buy_spot_premium').parent().show();
			
			$('#vol-breaks thead tr td:nth-child(4)').hide();
			$('#vol-breaks thead tr td:nth-child(3)').show();
			$('#vol-breaks tbody tr td:nth-child(5)').hide().find('input').val('');
			$('#vol-breaks tbody tr td:nth-child(4)').show();
			$('#vol-breaks tfoot td').attr('colspan', 5);
		}
		else {
			$('#_calc_method option[value="a"]').text('A: (spot + premium) * fine weight');
			$('#_calc_method option[value="b"]').text('B: (spot * fine weight) + premium');
			
			// clear and hide appropriate fields
			$('#_sell_spot_premium').val('').parent().hide();
			$('#_sell_price').parent().show();
			$('#_buy_spot_premium').val('').parent().hide();
			$('#_buy_price').parent().show();
			
			$('#vol-breaks thead tr td:nth-child(3)').hide();
			$('#vol-breaks thead tr td:nth-child(4)').show();
			$('#vol-breaks tbody tr td:nth-child(4)').hide().find('input').val('');
			$('#vol-breaks tbody tr td:nth-child(5)').show();
			$('#vol-breaks tfoot td').attr('colspan', 5);
		}
	}).change();
	
	// update bid and ask, and show formulas
	updateBidAskFields();
	
	// if post title is empty when selecting from the fiztrade item list,
	// set it to the name of the fiztrade item
	$('#_product_id').change(function () {
		if ($('#title').val() == '') {
			var familyName = $(this).find(':selected').parent().attr('label');
			
			$('#title').val(familyName + ' ' + $(this).find(':selected').text());
			// hide the placeholder label
			$('#title-prompt-text').hide(); // not quite it - this won't come back
		}
	});
	
	// adjusts for the placeholder tweakery above
	$('#title').change(function () {
		if ($('#title').val() == '') {
			$('#title-prompt-text').show();
		}
	});
	
						
	// this is exclusively for making sure old products load correctly
	if ($('.sell_option_field input:checked').length == 0) {
		
		var statusList = $('#woocommerce-product-data .type_box');
		if (!$('#chkSell', statusList).is(':checked')) {
			$('.sell_option_field [value="no"]').prop('checked', true);
		} 
		else {
			if ($('#chkCallA', statusList).is(':checked')) {
				$('.sell_option_field [value="callA"]').prop('checked', true);
			}
			else if ($('#chkCall', statusList).is(':checked')) {
				$('.sell_option_field [value="callPA"]').prop('checked', true);
			}
			else {
				$('.sell_option_field [value="yes"]').prop('checked', true);
			}
		}	
	}	
	if ($('.buy_option_field').length && $('.buy_option_field input:checked').length == 0) {
		
		if (!$('#chkBuy', statusList).is(':checked')) {
			$('.buy_option_field [value="no"]').prop('checked', true);
		} 
		else {
			if ($('#chkCallA', statusList).is(':checked')) {
				$('.buy_option_field [value="callA"]').prop('checked', true);
			}
			else if ($('#chkCall', statusList).is(':checked')) {
				$('.buy_option_field [value="callPA"]').prop('checked', true);
			}
			else {
				$('.buy_option_field [value="yes"]').prop('checked', true);
			}
		}					
	}
	// new items
	if ($('.sell_option_field input:checked').length == 0) {
		$('.sell_option_field [value="no"]').prop('checked', true);
		$('.buy_option_field [value="no"]').prop('checked', true);
	}
	
	// disable buy inputs when buy is unchecked, ditto sell
	$('input[name="sell_option"]').on('change', function () {
		switch ($('input[name="sell_option"]:checked').val()) {
			case 'no':
			case 'callPA':
				$('.sell').prop('readonly', true);
				break;
			case 'yes':
			case 'callA':
				$('.sell').prop('readonly', false);
				break;
		}		
	}).change();
	$('input[name="buy_option"]').on('change', function () {
		switch ($('input[name="buy_option"]:checked').val()) {
			case 'no':
			case 'callPA':
				$('.buy').prop('readonly', true);
				break;
			case 'yes':
			case 'callA':
				$('.buy').prop('readonly', false);
				break;
		}		
	}).change();

	$('input[name="_price_option"]').change(function () {
		if ($('input[name="_price_option"]:checked').val() == 'flat') {
			$('._premium_type_field input[type="radio"][value="flat"]').prop('checked', true).change();
			$('.spot-only, .spot-top').hide();
			$('label[for="_sell_price"]').text('Sell Price ($)');
			$('label[for="_buy_price"]').text('Sell Price ($)');
		}
		else {
			$('.spot-ask_field, .spot-bid_field, .spot-top, .show_if_dealer .update-btn').show();
			$('#spot-sell-price, #spot-buy-price, #_spot_metal').parent().show();
			// if ($('._premium_type_field input[type="radio"]:checked').val() == 'percent') {
				// $('._premium_type_field input').change();
			// }
			$('label[for="_sell_price"]').text('Flat Premium ($)');
			$('label[for="_buy_price"]').text('Flat Premium ($)');
		}
	}).change();
	
	// show retail price calculations for fiztrade & spot-based items
	// $('#sell-price').not(':hidden').html(showRetailSellCalc());
	// $('#buy-price').not(':hidden').html(showRetailBuyCalc());
	$('#spot-sell-price').not(':hidden').html(showSpotRetailSellCalc());
	$('#spot-buy-price').not(':hidden').html(showSpotRetailBuyCalc());
	//window.setInterval(updateBidAsk, 2000); // update bid and ask prices every two seconds
	
	$('#_sell_percent_premium, #_sell_flat_premium').change(function () {$('#sell-price').html(showRetailSellCalc())});
	$('#_buy_percent_premium, #_buy_flat_premium').change(function () {$('#buy-price').html(showRetailBuyCalc())});
	$('#_sell_spot_premium, #_sell_price').change(function () {$('#spot-sell-price').html(showSpotRetailSellCalc())});
	$('#_buy_spot_premium, #_buy_price').change(function () {$('#spot-buy-price').html(showSpotRetailBuyCalc())});
	$('#_calc_method').on('change', function () {
		$('#spot-sell-price').html(showSpotRetailSellCalc());
		$('#spot-buy-price').html(showSpotRetailBuyCalc());
	});
	$('#_sell_percent_premium, #_sell_flat_premium, #_buy_percent_premium, #_buy_flat_premium, #_sell_spot_premium, #_sell_price, #_buy_spot_premium, #_buy_price, #_calc_method')
		.change( function () { $('.result').formatCurrency() });
	
	//$('.disable-me').attr('disabled', 'true');
	
	// fill fields on new product screen when a fiztrade product is selected
	$('#_product_id').change(function () {
		updateBidAskFields();
		
		var thisObj = this;
		$.ajax({
			type: "GET",
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			data: { 'action' : 'product_data', 'code' : $(thisObj).val() },
			url: ajaxurl,
			success: function (response) {
				$('#_product_weight_fiztrade').val(response.Weight);
				
				// shipping weight and class
				$('#_weight').val(getKG(response.Weight));
				$('#product_shipping_class option').filter(function() {
					return $(this).text().trim() == response.MetalType.trim();
				}).prop('selected', true);
				
				// set Text content
				$('#content').val(response.Details); 
				// set Visual content
				tinymce.activeEditor.selection.setContent('<p>'+ response.Details +'</p>');
				
				// set category checkboxes
				$('#product_catchecklist label.selectit').each(function () {		
					//$(this).find('input').attr('checked', false);
					if ($(this).text().trim() == response.MetalType.trim()) {		// will expand this if we get more categories
						$(this).find('input').attr('checked', true);							// check the box for this metal
						$(this).parent().parent().prev().find('input').attr('checked', true);  // check the box for the parent of this metal
					}
				});
				// TODO: get other photos included somehow
				// SERVICE_URL is defined in dm_central.php
				var photoURL = response.ImagePath + '/' + response.Image;
				$('#gallery').attr('src', photoURL);
				$('#gallery').show();
				// set attributes
				wcSetAttribute('origin', response.Origin, true);
				wcSetAttribute('mint', response.Mint, true);
				wcSetAttribute('strike', response.Strike, true);
			}
		});
	});
	
	// fill fields on new product screen when a fiztrade product is selected
	$('#copy-desc').click(function () {	
		$.ajax({
			type: "GET",
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			data: { 'action' : 'product_data', 'code' : $('#_product_id').val() },
			url: ajaxurl,
			success: function (response) {
				// set Text content
				$('#content').val(response.Details); 
				// set Visual content
				tinymce.activeEditor.selection.setContent('<p>'+ response.Details +'</p>');
			}
		});
		return false;
	});
	
	
	// clicking the Update buttons will update their respective prices
	$('#update-ask').click(updateAskField);
	$('#update-bid').click(updateBidField);
	$('.update-spot').click(function () {
		updateSpots($('#_spot_metal').val());
	});
	
	$('#_spot_metal').change(function () {
		var selText = $('option:selected', this).text();
		$('label[for="spot-ask"]').text(selText + ' Spot');
		$('label[for="spot-bid"]').text(selText + ' Spot');
		updateSpots($(this).val());
	});
	
	$('#_product_weight').on('keyup', function () {
		if ($('input[value="spot"]:checked').length) {
			updateSpots($('#_spot_metal').val());
		}
	});
	
	$('#weight_unit').on('change', function () {
		if ($('input[value="spot"]:checked').length) {
			updateSpots($('#_spot_metal').val());
		}
	});

});