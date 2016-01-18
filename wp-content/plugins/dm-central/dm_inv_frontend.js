var countdown;

jQuery(document).ready(function($) {
	/* volume breaks popup */
	$('.single-product div[itemprop="offers"] .price').hover(function () {
		var pos = $(this).position();
		$('#volume-breaks').each(function () {
			$(this).css('display', 'block')
			$(this).css('position', 'absolute')
			//$(this).css('left', pos.left - $(this).width() + 50)
			$(this).css('top', pos.top + 50);
		});
	}, function () {
		$('#volume-breaks').hide();
	});
	$('#market-hours-link').click(function (e) {
		e.preventDefault();
	});

	/*    Checkout Page    */
	var page;
	if (window.location.href.indexOf('offer-checkout') != -1)
		page = 'offer-checkout';
	else if (window.location.href.indexOf('checkout') != -1)
		page = 'checkout';

	if ($('.dm-countdown').length) {
		// countdown
		function printNum() { 
				$('.dm-countdown').text(c); 
				c -= 1;
				if (c == 0) {
					expirePrice();
				}					
		}
		var c = 19;
		countdown = window.setInterval(printNum, 1000);
		
		// after 20 seconds, prevent page from submitting
		// new button reloads page instead
		//window.setTimeout(expirePrice, 20000);
	}

	// runs when ajax updates checkout screen
	$('body').on('updated_checkout', function () {
		if (c <= 1) {  // check if lock has expired
			expirePrice();
		}
	});

	function expirePrice() {
		// swap the Place Order button for the Update Price button
		$('#place_order').hide();
		$('#update-price').show();
		$('.countdown-area').hide();
		window.clearInterval(countdown);
	}
	
	$('#order_review').on('click', '#update-price', function (e) {
		e.preventDefault();
		$('#update-price')
			.prop('disabled', true)
			.text('Updating...');
		$('.countdown-area .timed-out').remove();
		
		// start countdown before doing ajax
		c = 19;
		countdown = window.setInterval(printNum, 1000);
	
		var trade = $('#place_order').hasClass('offer') ? 'sell' : 'buy';
		// do some ajax to get another lock token and refresh the prices
		$.ajax({
			url: ajaxurl,
			type: "POST",
			dataType: "json",
			beforeSend: function(x) {  // avoids problems on some clients
				if (x && x.overrideMimeType) {
					x.overrideMimeType("application/json;charset=UTF-8");
				}
			},
			data: { 
				'action':'lock',
				'trade': trade,
				'source': page
			},
			error: function(xhr, error, eText) {
				window.clearInterval(countdown);
				alert('Lock failed:' + error + ' ' + eText + "\n\nPlease refresh the page and try again.");
			},
			success: function(response, status, jqxhr) {
				if (response.error) {
					window.clearInterval(countdown);
					alert('Lock failed: ' + response.error + ' ' + "\n\nPlease refresh the page and try again.");
					return;
				}
				
				if (response.redirect) {
					window.location = response.redirect;
					return;
				}
				
				// update the prices
				for(code in response.lines)
				{
					$('.shop_table [data-product-code="'+ code +'"]').parent().find('.product-total').html(response.lines[code]);
				}
				$('.shop_table .cart-subtotal .amount').replaceWith(response.subtotal);
				// TODO: tax/shipping not included here
				$('.shop_table .order-total .amount').replaceWith(response.total);
				
				// update authorize.net fields
				// TODO - figure out how to generalize this
				// $('.payment_method_authorize_net_checkout input[name="x_amount"]').val(response.totalUnformatted);
				// $('.payment_method_authorize_net_checkout input[name="x_fp_sequence"]').val(response.x_fp_sequence);
				// $('.payment_method_authorize_net_checkout input[name="x_fp_timestamp"]').val(response.x_fp_timestamp);
				// $('.payment_method_authorize_net_checkout input[name="x_fp_hash"]').val(response.x_fp_hash);
				
				if (c > 0) {
					// put the buttons and countdown back
					$('#update-price').prop('disabled', false).hide();
					$('#place_order').show();
					$('.countdown-area').show();
				} 
				else {
					// timed out, try again
					$('#update-price').prop('disabled', false);
					$('.countdown-area').show().append('<span class="timed-out">Price lock timed out.  If this recurs, try removing line items from your cart.</span>');
				}
			},
			complete: function (jqxhr, status) {
				$('#update-price').text('Update Price');
			}
		});
	});
});