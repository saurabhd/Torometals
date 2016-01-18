jQuery(document).ready(function($) {
	if ($('#countdown').length) {
		// countdown
		function printNum() { 
				document.getElementById('countdown').innerHTML = c; 
				c -= 1;
		}
		var c = 19;
		for (var t=1000; t<20000; t+=1000) {
			window.setTimeout(printNum, t);
		}
		
		// after 20 seconds, prevent page from submitting
		// new button reloads page instead
		window.setTimeout(expirePrice, 20000);
	}
	
	$( 'a.edit_address' ).click(function( e ) {
		e.preventDefault();
		$( this ).hide();
		$( this ).closest( '.order_data_column' ).find( 'div.address' ).hide();
		$( this ).closest( '.order_data_column' ).find( 'div.edit_address' ).show();
	});
	
	$('.edit_address ._shipping_method_field input').click(function () {
		if ($(this).val() == 'local_pickup') {
			$('.edit_address p.form-field').hide();
		}
		else {
			$('.edit_address p.form-field').show();
		}
	}).filter(':checked').click();
	
	// change value of number of items to add to DG order select
	$('#order_line_items input.quantity').change(function () {
		var max = parseInt($(this).val()) || 0;
		var found = 0;
		$(this).closest('tr').find('.found').each(function() {
			found += parseInt($(this).text());
		});
		var toChange = $(this).closest('tr').find('#num_items');
		
		toChange.html('');
		for (var i = max - found; i>0; i--) {
			toChange
			 .append($("<option></option>")
			 .attr("value",i)
			 .text("Add " + i));
		}
	}).change();
	
	// adds shop order item to DG Order
	$('button[name="add-item"]').click(addClick);
	
	function expirePrice() {
		// swap the Place Order button for the Update Price button
		$('button[value="execute"]').hide();
		$('#countdown-area').hide();
		$('#countdown-area').after('<button id="update-price" class="button alt">Update Price</button><button id="forget" class="button alt">Cancel</button>');
		
		$('#update-price').on('click', function (e) {
			e.preventDefault();
		
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
					'qstring': location.search
				},
				success: function(response) {
					if (response.error)
					{
						alert('Lock failed:' + response.error + "\n\nPlease refresh the page and try again.");
					}
					else
					{
						// update the prices
						$('#execute-area .price .amount').replaceWith(response.subtotal);
						
						// put the buttons and countdown back
						$('#update-price').remove();
						$('#forget').remove();
						$('#place_order').show();
						$('button[value="execute"]').show();
						$('#countdown-area').show();
						$('#countdown').text("20");
						
						// run the countdown
						c = 19;
						for (var t=1000; t<20000; t+=1000) {
							window.setTimeout(printNum, t);
						}
						
						//expire again
						window.setTimeout(expirePrice, 20000);
					}
				}
			});
			return false;
		});
		
		$('#forget').click(function () {
			var url = location.href;
			var lockIndex = url.indexOf('&locked');
			url = url.slice(0, lockIndex);
			location.replace(url);
		});
	}
	
	function addClick () {
		var tableCell = $(this).parent();
		//var itemID = tableCell.attr('data-item');
		var itemID = tableCell.closest('tr').attr('data-order_item_id');
		var dgOrder = tableCell.find('select[name="dg_order"]').val();
		var numItems = tableCell.find('select#num_items').val();
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
				'action':'add_to_dg_order', 
				'itemID':itemID, 
				'orderID': $('#post_ID').val(), 
				'numItems':numItems,
				'dgOrder':dgOrder  
			},
			error: function(xhr, error, eText) {
				alert('Ajax failed:' + error + ' ' + eText);
			},
			success: function(response) {
				tableCell.html(response.newContent);
				// update num_items again
				$('#order_items_list input.quantity').change();
				// reattach this handler
				$('button[name="add-item"]').click(addClick);
				// if a new order was added, add it to the dropdowns for the other items on the page
				if (response.newDGOrder >= 0) {
					$('select[name="dg_order"]')
						.append('<option value="dg_order_' + response.newDGOrder + '">' + response.newDGOrder + '</option>')
						.val('dg_order_' + response.newDGOrder);					
				}
			}
		});
		return false;
	}
});