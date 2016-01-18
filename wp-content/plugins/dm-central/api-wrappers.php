<?php

// takes a woocommerce order object and locks prices of fiztrade items in it
// if $order is null, we check the cart instead
// trade may be 'buy' or 'sell'
// returns the result of the FizTrade API call
function dm_orders_lock_prices ($order=null, $trade='buy') {
	global $offer_cart;
	
	$post_items = array();
	if (!empty($order)) {
		//$trans_id = $order->id; // prolly not right
		foreach ($order->get_items() as $item_id => $item) {
			$product = $order->get_product_from_item($item);

			if ($product->product_type == 'fiztrade') {  // only care about fiztrade items
				//wp_die('order: '. $order->get_item_meta($item_id, '_qty', true));
				$post_items[] = array(
					'code' => $product->dg_id, 
					'transactionType' => $trade,
					'qty' => strval($order->get_item_meta($item_id, '_qty', true))
				);
			}
		}
	} else {
		$cart = $trade == 'buy' ? WC()->cart : $offer_cart;
		
		foreach ($cart->get_cart() as $cart_item) {
			$product = $cart_item['data'];
			if ($product->product_type == 'fiztrade') {  // only care about fiztrade items
				
				$post_items[] = array(
					'code' => $product->dg_id, 
					'transactionType' => $trade,
					'qty' => strval($cart_item['quantity'])  // needs to be a string for json_encode to work right
				);
			}
		}
	}
	
	if (count($post_items) > 0) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		$options = get_option('imag_mall_options_tech');		
		if ($options['stage_prod'] == 'staging') {
			$svcURL = SERVICE_URL_STAGE;
			$dealerToken = $options['dealer_token_staging'];
		} else {
			$svcURL = SERVICE_URL;
			$dealerToken = $options['dealer_token'];
		}
		
		$url = $svcURL  .'/FizServices/LockPrices/' . $dealerToken;
		
		$post = array(
			//'transactionId' => $order->id,
			//'transactionId' => com_create_guid(),
			'transactionId' => uniqid(), // random trans_id
			'items' => $post_items
		);	
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'User-Agent: My-funky-user-agent'));
		
		curl_setopt($curl, CURLOPT_URL , $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
		
		$json = curl_exec($curl);
		
		curl_close($curl);
		
		$result = json_decode($json, true);

		if (isset($result['error'])) {
			// TODO: log error
			// show error backend
			WC_Admin_Settings::add_error('Couldn\'t lock price: ' . $result['error'], 'error');
			// show error frontend
			if (function_exists('wc_add_notice'))
				wc_add_notice('Couldn\'t lock price: ' . $result['error'], 'error');
				
			set_transient('lock-err', true, 5);
		} else {		
			// save the locked data
			set_transient('lock-'. get_current_user_id(), $result, PRICE_GOOD_FOR);  //lock is good for PRICE_GOOD_FOR seconds

			// update cart, if necessary
			if (isset($cart)) {		
				// for ($i=0; $i<count($cart->cart_contents); $i++) {
					// $product = $cart->cart_contents[$i]['data'];
					// foreach ($result['prices'] as $lock_item) {
						// if ($lock_item['product'] == $product->dg_id) {
							// $product->price = $lock_item['price'];
						// }
					// }
				// }
				
				// force calculation of grand total
				define('WOOCOMMERCE_CHECKOUT', true);
				$cart->calculate_totals();
			}
		}
		
		return $result;		
	} else {	// lock not needed
		return false;
	}
}

// disallow checkouts when errors detected
function dm_lock_err_msg ($html) { 
	global $lock_err;
	$lock_err = get_transient('lock-err');
	if ($lock_err)
		return '<p id="place_order"><b>Error detected, can\'t submit</b></p> <style>.countdown-area { display:none }</style>'; 
	else
		return $html;
}
add_filter('woocommerce_order_button_html', 'dm_lock_err_msg', 90);

function dm_has_lock() {
	$lock = get_transient('lock-'. get_current_user_id());
		
	if ($lock === false)
		return false;
	else
		return true;
}

// execute a trade
function dm_orders_execute($order, $user_id = null) {
	// switch this flag to have debugging info shown on screen as
	$debugging = false;
	
	$lock = get_transient('lock-'. get_current_user_id());
	
	if ($lock === false) {
		$result['error'] = 'Lock token has expired.  Please lock prices again.';
	} else {
		$options = get_option('imag_mall_options_tech');		
		if ($options['stage_prod'] == 'staging') {
			$svcURL = SERVICE_URL_STAGE;
			$dealerToken = $options['dealer_token_staging'];
		} else {
			$svcURL = SERVICE_URL;
			$dealerToken = $options['dealer_token'];
		}
			
		if (is_int($order))
			$order = new WC_Order($order);
			
		
		global $blog_id;
		if (empty($user_id))
			$user_id = $order->user_id;

		
		$post = array(
			'transactionId' => $lock['transactionId'],
			'referenceNumber' => strval($order->id),
			'lockToken' => $lock['lockToken'],
			'traderId' => $options['trader_id']
		);
		
		// add shipping stuff to $post
		switch ($order->shipping_method) {
			case 'hold':
				$post['shippingOption'] = 'hold';
				break;
			case 'store':
				$post['shippingOption'] = 'store';
				break;
			case 'local_pickup':
				$post['shippingOption'] = 'ship_to_me';
				break;
			case 'local_delivery':
			default:
				$post['shippingOption'] = dm_inv_get_default_shipping($user_id);
		}
		
		$debug .= 'Order shipping method: ' . $order->shipping_method . '<br/>'.
			'FizTrade shippingOption: ' . $post['shippingOption'] .'<br/>';
		
		// add address if drop shipping
		if ($post['shippingOption'] == 'drop_ship' || $post['shippingOption'] == 'hold') {
			$post['dropShipInfo'] = array(
				'name' => $order->shipping_first_name .' '. $order->shipping_last_name,
				'address1' => $order->shipping_company,
				'address2' => $order->shipping_address_1,
				'address3' => $order->shipping_address_2,
				'city' => $order->shipping_city,
				'state' => $order->shipping_state,
				'postalCode' => $order->shipping_postcode,
				'country' => $order->shipping_country
			);
		}
		
		$debug .= "Post body: ". json_encode($post) . "<br/><br/>";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'User-Agent: My-funky-user-agent'));
		curl_setopt($curl, CURLOPT_POST, true);
		
		$url = $svcURL .'/FizServices/ExecuteTrade/' . $dealerToken;
		
		curl_setopt($curl, CURLOPT_URL , $url);
		
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
		
		$json = curl_exec($curl);
		
		$debug .= "Response: ". $json;
		
		curl_close($curl);
		
		$result = json_decode($json, true);
		// TODO: detect order rejection
		
		if ($result == null) {
			$result['error'] = 'FizTrade returned invalid response - trade may or may not have executed.';
			if ($debugging)
				$result['error'] .= $debug;
		}
		
		// TODO: if necessary, set shipping method on DG order
		
		// clear the lock
		delete_transient('lock-'. get_current_user_id());
		
		if ($debugging) {
			WC_Admin_Settings::add_error($debug);
		}
	}
	// TODO: log errors
	
	return $result;
}

// cancel a locked trade
function dm_orders_cancel() {
	$lock = get_transient('lock-'. get_current_user_id());
	
	if ($lock === false) {
		// no lock, no need to cancel
		return array('canceled' => '');
	} else {
		$options = get_option('imag_mall_options_tech');		
		if ($options['stage_prod'] == 'staging') {
			$svcURL = SERVICE_URL_STAGE;
			$dealerToken = $options['dealer_token_staging'];
		} else {
			$svcURL = SERVICE_URL;
			$dealerToken = $options['dealer_token'];
		}
			
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'User-Agent: My-funky-user-agent'));
		
		$url = $svcURL .'/FizServices/CancelTrade/' . $dealerToken .'/'. $lock['lockToken'];
			
		curl_setopt($curl, CURLOPT_URL , $url);
		
		$result = json_decode($json, true);
		
		// clear the lock
		delete_transient('lock-'. get_current_user_id());
				
		return $result;
	}
}

/// generalized function for posting to FizTrade API
/// parameters: $method is one of the API method names, $post_data is a data array to be sent
function dm_api_post($method, $post_data) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_POST, true);
	$options = get_option('imag_mall_options_tech');		
	if ($options['stage_prod'] == 'staging') {
		$svcURL = SERVICE_URL_STAGE;
		$dealerToken = $options['dealer_token_staging'];
	} else {
		$svcURL = SERVICE_URL;
		$dealerToken = $options['dealer_token'];
	}
	
	$url = $svcURL  .'/FizServices/'. $method .'/'. $dealerToken;
	
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'User-Agent: My-funky-user-agent'));
	
	curl_setopt($curl, CURLOPT_URL , $url);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
	
	$json = curl_exec($curl);
	
	curl_close($curl);
	
	$result = json_decode($json, true);
	
	if (empty($result))
		return new WP_Error('fiztrade_error', 'Failed POSTing to FizTrade.');
	else if (array_key_exists('error', $result))
		return new WP_Error('fiztrade_error', $result['error']);
	else
		return $result;
}

/// generalized function for getting from FizTrade API
/// parameters: $method is one of the API method names, $get_param is a the parameter appended to the URL
function dm_api_get($method, $get_param = '') {	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	$options = get_option('imag_mall_options_tech');		
	if ($options['stage_prod'] == 'staging') {
		$svcURL = SERVICE_URL_STAGE;
		$dealerToken = $options['dealer_token_staging'];
	} else {
		$svcURL = SERVICE_URL;
		$dealerToken = $options['dealer_token'];
	}
	
	$url = $svcURL  .'/FizServices/'. $method .'/'. $dealerToken .'/'. $get_param;
	
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'User-Agent: My-funky-user-agent'));
	
	curl_setopt($curl, CURLOPT_URL , $url);

	$json = curl_exec($curl);
	curl_close($curl);

	$result = json_decode($json, true);
	
	if (empty($json))
		return new WP_Error('fiztrade_error', 'Failed to GET from FizTrade.');
	else if (array_key_exists('error', $result))
		return new WP_Error('fiztrade_error', $result['error']);
	else
		return $result;
}
?>