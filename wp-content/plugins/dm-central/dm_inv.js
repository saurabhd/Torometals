// Helps with the display of the product catalog administration screens

Date.prototype.stdTimezoneOffset = function() {
    var jan = new Date(this.getFullYear(), 0, 1);
    var jul = new Date(this.getFullYear(), 6, 1);
    return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
}

Date.prototype.dst = function() {
    return this.getTimezoneOffset() < this.stdTimezoneOffset();
}

jQuery(document).ready(function($) {
	// keeps IE from caching ajax calls
	$.ajaxSetup({cache: false});
	
	// used by $.fn.updateAsk, below
	function _update(action, postID, jqObj) {
		var input = { 
			action: action,
			productID: postID 
		};
		
		$.post(ajaxurl, input, function(response) {
			//alert('response: ' + response);
			if (jqObj.is('input')) {
				jqObj.val(response);  // TODO: this will have tags around it
			} else {
				jqObj.html(response); // this wipes out interior tags - may revisit
			}
		});
	}
	
	function updateCurrentPrices(e) {
		if (typeof e != 'undefined' && e.type == 'click') {
			e.target.focus();
		}
		
		var productID = $('.price [data-product-id]').attr('data-product-id');
			
			
		$.ajax({
			type: "GET",
			data: { 
				'action':'get_prices',
				'productID':productID
			},
			url: ajaxurl,
			success: function (response) {
				//console.log(response);
				var priceInfo = JSON.parse(response);
				
				if (priceInfo['error']) {
					console.log(priceInfo['error']);
					return;
				}
				
				$('div[itemprop="offers"] .price .amount').html(priceInfo['tiers']['1']['ask']).formatCurrency({ symbol: currencySymbol });
				$('#volume-breaks table tr').each(function () {
					var tier = $(this).data('tier');
					$('.amount', this).text(priceInfo['tiers'][tier]['ask']).formatCurrency({ symbol: currencySymbol });
				});
				
				$('div[itemprop="demands"] .price .amount').html(priceInfo['tiers']['1']['bid']).formatCurrency({ symbol: currencySymbol });
									
				var updated = new Date(priceInfo['time'] * 1000); // Date takes milliseconds
				var timeStr = updated.toLocaleTimeString();
				var dateStr = updated.toLocaleDateString(); 
				// IE9 and safari don't respect toLocaleDateString options
				// fix it in English - don't guess I can do any other language
				var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
				for (var i=0; i<7; i++) {
					dateStr = dateStr.replace(days[i]+', ', '');
				}
				if (timeStr.slice(-2) != 'AM' && timeStr.slice(-2) != 'PM') {
					timeStr = timeStr.slice(0, -4);
				}
				
				$('#price-time .timestamp').text('Prices updated at ' + timeStr +' '+ dateStr);
				
				// make refresh prices button do AJAX call instead of reloading page
				$('#price-time .refresh-button').off('click').on('click', function (e) {
					e.preventDefault();
					updateCurrentPrices(e);			
				});
				
				if (typeof e != 'undefined' && e.type == 'click') {
					e.target.blur();
				}
				
				// TODO: outage message
			}
		});
	}
	
	function updateArchivePrices(e) {
		if (typeof e != 'undefined' && e.type == 'click') {
			e.target.focus();
		}
		
		// build list of products for which to get prices	
		var productList = [];
		// selector excludes products that don't have prices listed
		$('.product .price .amount')
		.closest('.product').each(function () { 
			var classes = $(this).attr('class').split(' ');
			var splits;
			for (var i=0; i<classes.length; i++) {
				if (classes[i].search('post-') != -1) {
					splits = classes[i].split('-');
					productList.push(splits[1]);
				}
			}
		});
		// console.log('productList:');
		// console.log(productList);
			
		$.ajax({
			type: "GET",
			data: { 
				'action':'get_archive_prices',
				'productList':productList
			},
			url: ajaxurl,
			success: function (response) {
				//console.log(response);
				var data = JSON.parse(response);
				var productInfo = data['product_info'];
				
				if (response['error']) {
					console.log(response['error']);
					return;
				}
				
				var list = $('.products');
				$.each(productInfo, function (index, product) {				
					var postID = product['retailProductCode'];
					$('.post-'+postID+' .price .amount', list).text(product['tiers']['1']['ask']).formatCurrency({ symbol: currencySymbol });
				});
				
				var updated = new Date(data['time'] * 1000); // Date takes milliseconds
				var dateStr = updated.toLocaleDateString(); 
				// IE9 and safari don't respect toLocaleDateString options
				// fix it in English - don't guess I can do any other language
				var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
				for (var i=0; i<7; i++) {
					dateStr = dateStr.replace(days[i]+', ', '');
				}
				$('#price-time .timestamp').text('Prices updated at ' + updated.toLocaleTimeString() +' '+ dateStr);
				
				// make refresh prices button do AJAX call instead of reloading page
				$('#price-time .refresh-button').off('click').on('click', function (e) {
					e.preventDefault();
					updateArchivePrices(e);			
				});
				
				if (typeof e != 'undefined' && e.type == 'click') {
					e.target.blur();
				}
			}
		});
	}

	function _updateTicker(jqObj) {
		// var test = SERVICE_URL + '/FizServices/GetSpotPriceData/' + INT_TOKEN;
		$.ajax({
			type: "GET",
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			data: { 'action':'ticker' },
			url: ajaxurl,
			//url: '/data/ticker.json',
			//url:  SERVICE_URL + '/FizServices/GetSpotPriceData/' + INT_TOKEN,
			success: function (response) {
				var metals = ['gold', 'silver', 'platinum', 'palladium'];
				var metal;
				for (var m=0; m<metals.length; m++) {
					metal = metals[m];
					jqObj.find('.pt-'+ metal +' .spot-ask').text(response[metal +'Ask']).formatCurrency();
					jqObj.find('.pt-'+ metal +' .spot-bid').text(response[metal +'Bid']).formatCurrency();
					delta = response[metal +'Change'];
					jqObj.find('.pt-'+ metal +' .price-arrow-box').each(function () {
						//$(this).text(Math.abs(delta)).formatCurrency({symbol:''});
						$(this).removeClass('down').removeClass('up');
						$(this).addClass(delta >= 0 ? 'up' : 'down');
					});
					jqObj.find('.pt-'+ metal +' .price-delta').each(function () {
						$(this).text(Math.abs(delta)).formatCurrency({symbol:''});
						$(this).removeClass('down').removeClass('up');
						$(this).addClass(delta >= 0 ? 'up' : 'down');
					});
				}
				// fiztrade provides timestamp as central time
				var today = new Date();	
				var centralSplits = response['timestamp'].split(' ');
				// add year and timezone
				var centralString = centralSplits[0] + ' ' + centralSplits[1] + ' ' + centralSplits[2] + ' ' + today.getFullYear() + ' ' + centralSplits[3] + ' ' + centralSplits[4] + (today.dst() ? ' CDT' : ' CST');
				//var centralString = response['timestamp'] + (today.dst() ? ' CDT' : ' CST');
				//console.log('centralString = ' + centralString);
				var updated = new Date(centralString);
				// var dateMess = response['spotTime'];
				// console.log(dateMess);
				// var epochMilli = dateMess.match('/[0-9]+/');
				// console.log(epochMilli);
				// var updated = new Date(dateMess.match('/[0-9]+/'));
				
				// check if this is stale
				//console.log(today + ' - ' + updated + ' = ' + (today - updated));
				if (today - updated > 300000) // 5 minutes old => stale
					$('#ticker-timestamp').addClass('stale');
				else
					$('#ticker-timestamp').removeClass('stale');
				
				var dateStr = updated.toLocaleDateString(); 
				// IE9 and safari don't respect toLocaleDateString options
				// fix it in English - don't guess I can do any other language
				var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
				for (var i=0; i<7; i++) {
					dateStr = dateStr.replace(days[i]+', ', '');
				}
				$('#ticker-timestamp .timestamp').text('Updated ' + updated.toLocaleTimeString() +' '+ dateStr);
				
				$('.ticker-wrapper').css('visibility', 'visible');
			}
		});
	}

	// update the selected elements with the dealer's ask price
	// Usage: $(selectorToUpdate).updateAsk(productPostID);
	// or $(selectorToUpdate).updateAsk(); when the select element has a data-product-id attribute
	$.fn.updateAsk = function(postID) {
		this.each(function() {
			var id = typeof postID !== 'undefined' ? postID : $(this).attr('data-product-id');
			var selected = $(this);

			if (typeof id !== 'undefined') {
				// TODO: this updates each item individually - would prefer one ajax call per unique data-product-id
				_update('ask_price', id, selected);
			}
		});
	}

	// update the selected elements with the dealer's bid price
	// Usage: $(selectorToUpdate).updateBid(productPostID);
	// or $(selectorToUpdate).updateBid(); when the select element has a data-product-id attribute
	$.fn.updateBid = function(postID) {
		this.each(function() {
			var id = typeof postID !== 'undefined' ? postID : $(this).attr('data-product-id');
			var selected = $(this);
			
			if (typeof id !== 'undefined') {
				setInterval(function() {_update('bid_price', id, selected);}, 2000);
			}
		});
	}
	
	// update the selected element with the four spot prices
	// Usage: $(selectorToUpdate).updateTicker();
	$.fn.updateTicker = function() {
		this.each(function() {
			//var getDelta = typeof includeDelta !== 'undefined' ? includeDelta : false;
			var selected = $(this);
			
			setInterval(function() {_updateTicker(selected);}, 2000);
		});
	}
	
	// update the selected elements with the dealer's bid price
	// Usage: $(selectorToUpdate).updateSpot(metal, includeDelta);
	$.fn.updateSpot = function(metal, includeDelta) {
		this.each(function() {
			var getDelta = typeof includeDelta !== 'undefined' ? includeDelta : false;
			var selected = $(this);
			
			if (typeof metal !== 'undefined') {
				setInterval(function() {_update(getDelta ? 'spot_and_delta' : 'spot', metal, selected);}, 2000);
			}
		});
	}
	
	/************* MAIN *************/

	
	//$('.update-ask').updateAsk();
	//$('.update-bid').updateBid();
	if ($('.single-product .refresh-button').length) {
			updateCurrentPrices();
	}
	
	if ($('.post-type-archive-product .refresh-button').length) {
			updateArchivePrices();
	}
	
	
	$('.gold-update').updateSpot('gold', true);
	$('.silver-update').updateSpot('silver', true);
	$('.platinum-update').updateSpot('platinum', true);
	$('.palladium-update').updateSpot('palladium', true);
	$('.ticker-wrapper').updateTicker();
	
	var now = new Date();
	$('.woocommerce .cart [name="update_cart"]').val('Update Cart').before('<span class="timestamp">Cart at ' + now.toLocaleTimeString() +' '+ now.toLocaleDateString() + '</span>');
	
	// paypal
	$('#order_review').on('click', '#place_order', function () {
		// keep button from being clicked twice
		//setTimeout($(this).off('click'), 1);
		
		
		if ($('#payment_method_paypal').is(':checked')) {
			
			$.ajax({
				type: "POST",
				beforeSend: function(x) {  // avoids problems on some clients
					if (x && x.overrideMimeType) {
						x.overrideMimeType("application/json;charset=UTF-8");
					}
				},
				data: { 'action':'paypal_notify' },
				url: ajaxurl
			});
		
		}
	});
	
});
