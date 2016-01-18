<?php
/*
	This customizes the Woocommerce->Orders table in the admin and 
	the Order/Offer history screen user side.
*/
define('PRICE_GOOD_FOR', 20);  // price on confirmation screen is good for 20sec 

function dm_orders_shipping_label_button ($order) {
	global $woocommerce;
	global $post;
	
	if ($post->post_type != "shop_order")
		return;
		
	echo '<input type="button" id="shipping-label" value="Shipping Label" />';
	$url = plugins_url('order-shipping-label.php', __FILE__);
	
	/*$woocommerce->add_inline_js("
		$('#shipping-label').click(function () {
			newwindow=window.open('$url','name','height=375,width=600');
			if (window.focus) {newwindow.focus()}
			return false;
		});
	");*/
	?>
	<noscript>
		<style>#shipping-label { display:none; }</style>
		<a href="<?php echo $url; ?>">Shipping Label</a>
	</noscript>
	
	<?php
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'dm_orders_shipping_label_button');

// determine if a given order may be automatically forwarded to fiztrade
// trade may be order or offer
function dm_orders_auto_fwd_eligible($order, $trade = 'order') {
	global $blog_id;
	
	$user_id = $order->user_id;
	if ($user_id == 0)
		$user_id = get_current_user_id(); // order doesn't have user id set when in pending state
		
	if (!user_can($user_id, 'customer')) {
		// cancel auto-forwards for unverified customers
		return false;
	}
	
	if ($trade != 'offer' && dm_orders_enough_local_stock($order)) {
		// if there's enough local stock, we don't want to auto-forward
		return false;
	}
	
	$tech_options = get_option('imag_mall_options_tech');
	
	$debug .= '<br />Tech options: '. print_r($tech_options, true);
	
	$temp = array_map( function ($a) { return $a[0]; }, get_user_meta(get_current_user_id()));
	if(is_multisite()) {
		// filter out auto-forward settings from other sites
		$user_meta = array();
		foreach ($temp as $key => $val) {
			if (strpos($key, 'wp_'. $blog_id) !== false) {
				$new_key = str_replace('wp_'. $blog_id .'_', '',$key);
				$user_meta[$new_key] = $val;
			}
		}
	} else {
		$user_meta = $temp;
	}
	$debug .= '<br />User options: '. print_r($user_meta, true);
	
	// check if user has individual auto-trade settings
	if (isset($user_meta['auto_'. $trade]) && $user_meta['auto_'. $trade] != 'inherit') {
		// override tech options
		$tech_options = $user_meta;
		$debug .= '<br /><b>Overriding</b>';
	}
	
	// php parses "false" as true - fix that
	foreach($tech_options as $key => $val) {
		if ($val === 'false')
			$tech_options[$key] = false;
	}
	
	$debug .= '<br />Result options: '. print_r($tech_options, true);
	
	if ($tech_options['auto_'. $trade]) {
		
		if ($tech_options['auto_'. $trade .'_lt'])	{
			// check that the total of the fiztrade item prices is not more than the set amount
			$total_fiztrade_items = 0;
			foreach ($order->get_items() as $item) {
				$product = $order->get_product_from_item($item);
				if ($product->product_type == 'fiztrade') {
					$total_fiztrade_items += $order->get_line_total($item);
				}
			}
			$debug .= '<br />FizTrade item total: '. $total_fiztrade_items;
			if ($total_fiztrade_items >= $tech_options['auto_'. $trade .'_lt_amount'])
				$amount_cond = false;
			else
				$amount_cond = true;
			$debug .= ' Eligible? '. $amount_cond ? 'yes' : 'no';
			//wp_die($debug);
			
			return $amount_cond;
		} else {
			return true;
		}		
	}	
	return false;
}


function dm_orders_enough_local_stock($order_or_item) {
	if (get_class($order_or_item) == 'WC_Order') {			
		// check if there's enough local stock for an entire order
		foreach ($order_or_item->get_items() as $item) {
			$product = $order_or_item->get_product_from_item($item);
			
			if (!$product->managing_stock())
				return false;
				
			if ($product->product_type == 'fiztrade') {
				$ordered_qty = $item['item_meta']['_qty'][0];
				
				if ($product->stock < $ordered_qty)
					return false;					
			}
		}
		// all order items had enough
		return true;
	} else {
		// check if there's enough local stock for a line item in an order
		$product = $order_or_item->get_product_from_item($order_or_item);
		if (!$product->managing_stock())
			return false;
		$ordered_qty = $order_or_item['_qty'];
		if (!$product->has_enough_stock($ordered_qty))
			return false;	
		else
			return true;
	}
}	

function dm_orders_ask_price_lockin($productID) {
	set_transient(get_current_user_id( ) . '_ask_lockin' , dm_inv_get_sell_price($productID), PRICE_GOOD_FOR);
}

function dm_orders_bid_price_lockin($productID) {
	set_transient(get_current_user_id( ) . '_bid_lockin' , dm_inv_get_bid_price($productID), PRICE_GOOD_FOR);
}

// lock prices when the checkout page is loaded
add_action('woocommerce_review_order_before_order_total', 'dm_orders_lock_prices');


function dm_orders_table_actions($actions, $order) {
	global $typenow;
	
	$outputActions = $actions;
	
	foreach ($order->get_items() as $orderItem) {
		$product = get_product($orderItem['product_id']);
		if ($product->product_type == 'fiztrade') {
			if ( in_array( $order->get_status(), array( 'pending', 'on-hold', 'processing' ))) {
				// don't allow dealer admins to complete orders that include fiztrade items
				unset($outputActions['processing']);
				unset($outputActions['complete']);
				
				// check for id of locked order, if it exists
				$locked_order = $_GET['ft_locked'];
				
				if ($locked_order == $order->id) {
					// just locked price, so show option to execute
					$outputActions['execute'] = array(
						'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=dm-orders-execute&order_id=' . $order->id ), 'dm-orders-execute' ),
						'name' 		=> 'Execute Trade',
						'action' 	=> 'execute',
						'image_url' => plugins_url() .'/woocommerce/assets/images/icons/complete.png' //plugins_url('images/images.jpg',  __FILE__)
					);
					// option to cancel
					$outputActions['cancel'] = array(
						'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=dm-orders-cancel' ), 'dm-orders-cancel' ),
						'name' 		=> 'Cancel Trade',
						'action' 	=> 'cancel',
						'image_url' => plugins_url() .'/woocommerce/assets/images/icons/delete_10.png' //plugins_url('images/images.jpg',  __FILE__)
					);
					// don't need option to view
					unset($outputActions['view']);
				} else {
					// add Forward and Fulfill buttons
					$outputActions['forward'] = array(
						'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=dm-orders-forward&order_id=' . $order->id ), 'dm-orders-forward' ),
						'name' 		=> 'Forward to FizTrade',
						'action' 	=> 'forward',
						'image_url' => plugins_url() .'/woocommerce/assets/images/icons/complete.png' //plugins_url('images/images.jpg',  __FILE__)
					);
					// $outputActions['fulfill'] = array(
						// 'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=dm-orders-fulfill-locally&order_id=' . $order->id ), 'dm-orders-fulfill-locally' ),
						// 'name' 		=> 'Fulfill from local inventory',
						// 'action' 	=> 'fulfill',
						// 'image_url' => plugins_url() .'/woocommerce/assets/images/icons/processing.png'
					// );
				}
			}
		}
	}
	
	
	//if (!dm_orders_contains_unordered($order->get_items())) {
	// if any items are in a fiztrade order, don't show the option to forward
	if (($typenow == 'shop_order' && dm_orders_contains_ordered($order->get_items())) ||
		($typenow == 'shop_offer' && dm_orders_contains_offered($order->get_items()))) {
		unset($outputActions['forward']);
	}
	
	return $outputActions;
}
add_filter( 'woocommerce_admin_order_actions', 'dm_orders_table_actions', 50, 2);

function dm_orders_table_countdown ($order) {	

	$locked_order = $_GET['ft_locked'];
	if ($locked_order == $order->id) { // check that this order matches the lock
		$lock = get_transient('lock-'. get_current_user_id());
		
		if ($lock == false) {
			echo 'Lock expired.  Reload page and get another lock.';
		} else {
			// re-calculate price based on lock
			$lock_prices = $lock['prices'];
			$fizTotal = 0;
			foreach ($lock_prices as $item) {
				$fizTotal += $item['amount'];
			}
			
			//
			echo '<div id="countdown-area">FizTrade price: '. woocommerce_price($fizTotal) .'<br/>';
			echo 'Price valid for <span id="countdown">'. PRICE_GOOD_FOR .'</span>sec'; // allow two seconds for credit card processing
		}
	}
}
add_action('woocommerce_admin_order_actions_start', 'dm_orders_table_countdown');

function dm_orders_table_countdown_end ($order) {	

	$locked_order = $_GET['ft_locked'];
	if ($locked_order == $order->id) { // check that this order matches the lock
		$lock = get_transient('lock-'. get_current_user_id());
		
		if ($lock == false) {
			// do nothing
		} else {
			// close the div opened in previous funciton
			echo '</div>';
		}
	}
}
add_action('woocommerce_admin_order_actions_end', 'dm_orders_table_countdown_end');

// performed when Forward to FizTrade is clicked
function dm_orders_forward_click() {
	if ( !is_admin() ) die;
	if ( !current_user_can('edit_shop_orders') ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce' ) );
	//if ( !check_admin_referer('dm-orders-forward')) wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce' ) );
	$order_id = isset($_GET['order_id']) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
	if (!$order_id) die;

	$trade = get_post_type($order_id) == 'shop_offer' ? 'sell' : 'buy';
	$order = new WC_Order($order_id);
	$result = dm_orders_lock_prices($order, $trade);
	
	if (isset($result['error'])) {
		$old_errors = get_transient('error-'. get_current_user_id());
		$errors = $old_errors . $result['error'];
		set_transient('error-'. get_current_user_id(), $errors, 10);
	}
	
	//$order->update_status( 'completed' );
	
	wp_safe_redirect( wp_get_referer() .'&ft_locked='. $order_id);
}
add_action('wp_ajax_dm-orders-forward', 'dm_orders_forward_click');

// performed when Execute Trade is clicked
function dm_orders_execute_click() {
	if ( !is_admin() ) die;
	if ( !current_user_can('edit_shop_orders') ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce' ) );
	//if ( !check_admin_referer('dm-orders-forward')) wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce' ) );
	$order_id = isset($_GET['order_id']) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
	if (!$order_id) die;
	
	$result = dm_orders_execute($order_id);
	
	if (isset($result['error'])) {
		$old_errors = get_transient('error-'. get_current_user_id());
		$errors = $old_errors . $result['error'];
		set_transient('error-'. get_current_user_id(), $errors, 10);
		
	} else {
		// add new order to dg_orders list
		$target_type = get_post_type($order_id) == 'shop_offer' ? 'dg_offer' : 'dg_order';
		$dg_order_id = dm_inv_copy_to_dg_order('all', $order_id, 'new', 'all', $target_type);	
		$dg_order = new DG_Order($dg_order_id);
		$dg_order->update_status('completed', 'FizTrade accepted order. Confirmation number: '. $result['confirmationNumber'][0]);
	}
	
	//$order->update_status( 'completed' );
	$url = wp_get_referer();
	$strip_index = strpos($url, '&ft_locked');
	$url = substr($url, 0, $strip_index);
	
	wp_safe_redirect( $url );
}
add_action('wp_ajax_dm-orders-execute', 'dm_orders_execute_click');

// performed when Cancel Trade is clicked
function dm_orders_cancel_click() {
	if ( !is_admin() ) die;
	if ( !current_user_can('edit_shop_orders') ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce' ) );

	$result = dm_orders_cancel();
	
	if (isset($result['error'])) {
		$old_errors = get_transient('error-'. get_current_user_id());
		$errors = $old_errors . $result['error'];
		set_transient('error-'. get_current_user_id(), $errors, 10);
	}
	
	$url = wp_get_referer();
	$strip_index = strpos($url, '&ft_locked');
	$url = substr($url, 0, $strip_index);
	
	wp_safe_redirect( $url );
}
add_action('wp_ajax_dm-orders-cancel', 'dm_orders_cancel_click');

// after an order is paid, auto-forwards it to FizTrade as appropriate
function dm_orders_auto_forward($order_id) {
	if (get_post_type($order_id) != 'shop_order')
		return;
	
	$order = new WC_Order($order_id);
	
	// don't auto-foward if any items have been forwarded
	if (dm_orders_contains_ordered($order->get_items()))
		return;
	
	if (dm_orders_auto_fwd_eligible($order)) {
		$order->add_order_note('Auto-forwarding order to FizTrade.');
		
		// copy order data to new DG order
		$dg_order_id = dm_inv_copy_to_dg_order('all', $order, 'new');
				
		// Execute Trade
		$result = dm_orders_execute($dg_order_id, $order->user_id);
		
		$old_status = $order->get_status();
		if (isset($result['error'])) {
			// trade didn't go through, so set it up as a manual trade for an admin to submit later
			$order->add_order_note('Automatic trade failed.  Please submit manually');
			
			$dg_order = new DG_Order($dg_order_id);
			$dg_order->update_status('processing', 'Automatic trade failed.  Please submit manually');
			do_action('dg_trade_failure', $dg_order_id);
		} else {
			if ($old_status == 'processing')
				$order->update_status('completed', 'FizTrade accepted order. Confirmation number: '. $result['confirmationNumber'][0]);
			else
				$order->add_order_note('FizTrade accepted order. Confirmation number: '. $result['confirmationNumber'][0]);
				
			$dg_order = new DG_Order($dg_order_id);
			$dg_order->update_status('completed', 'FizTrade accepted order. Confirmation number: '. $result['confirmationNumber'][0]);			
		}
	}
}
add_action('woocommerce_payment_complete', 'dm_orders_auto_forward');
add_action('woocommerce_order_status_on-hold', 'dm_orders_auto_forward');

// special auto-forward for paypal
// TODO: may want merge this function and previous to have all orders execute on pending
function dm_orders_paypal_auto_forward($order_id, $order_posted) {
	$order = new WC_Order($order_id);
	$is_paypal_order = get_transient('paypal_order_from_' . get_current_user_id());
	
	if ($is_paypal_order) {
		dm_orders_auto_forward($order_id);
		delete_transient('paypal_order_from_' . get_current_user_id());
	}
}
#add_action('woocommerce_checkout_update_order_meta', 'dm_orders_paypal_auto_forward');

function dm_orders_paypal_save () {
	set_transient('paypal_order_from_' . get_current_user_id(), true, 30);
	echo 'success';
	die();
}
add_action('wp_ajax_paypal_notify', 'dm_orders_paypal_save');

function dm_orders_update_lock() {
	global $logout_redirect, $offer_cart;
	//$response['error'] = 'Testing: '. is_user_logged_in();
	//if (!is_user_logged_in()) {
	if (isset($logout_redirect)) {
		$response['redirect'] = add_query_arg('auto-logout', 'true', wp_login_url());
	};

	$trade = isset($_POST['trade']) ? $_POST['trade'] : 'buy';
	$source = isset($_POST['source']) ? $_POST['source'] : 'back-end';
	
	if(isset($_POST['qstring'])) {
		parse_str(trim($_POST['qstring'], "?"), $query);
		$order_id = isset($query['locked']) ? $query['locked'] : $query['post'];
		if (empty($order_id))
			$order_id = $query['ft_locked'];
		
		$order = new WC_Order($order_id);
		$result = dm_orders_lock_prices($order, $trade);
		echo json_encode($result);
		die();
		// being called from back end, leave out dealer markup
		$add_markup = false;
	} else {
$response['debug_a'] = time();
		$ft_prices = dm_orders_lock_prices(null, $trade);
$response['debug_lock'] = $ft_prices['debug'];
$response['debug_b'] = time();	
		// this is being called from front end, include dealer markup
		$add_markup = true;
	}
	if (isset($ft_prices['error']))
		$response['error'] = $ft_prices['error'];

	// ajax response will be productCode1 = amount1, productCode2 = amount2, 'subtotal' = subtotal
	$subtotal = 0;
	foreach ($ft_prices['prices'] as $price_item) {
		$dg_price = abs($price_item['amount']);
		
		if ($add_markup) {	
			// handled below
			// $product = get_product_from_dg_id($price_item['product']);
			// $out_price = $trade == 'buy' ? $product->get_price('buy') : $product->get_price('sell');
			// $out_price *= $price_item['qty'];
		} else {
			$out_price = $dg_price * $price_item['qty'];
			$product = null;
		}
			
		$response['lines'][$price_item['product']] = array('price' => woocommerce_price($out_price), 'product' => $product);	
		$subtotal += $out_price;
	}

	// throw in dealer items
	if ($source == 'checkout') {
		foreach (WC()->cart->get_cart() as $cart_item) {
			$product = $cart_item['data'];
			if ($product->product_type == 'fiztrade') 
				$js_id = $product->dg_id;
			else
				$js_id = 'DM-'. $product->id;
			
			$js_price = $product->get_price('buy') * $cart_item['quantity'];
			$response['lines'][$js_id] = array('price' => woocommerce_price($js_price), 'product' => $product);
		}
	} elseif ($source == 'offer-checkout') {
		foreach ($offer_cart->get_cart() as $cart_item) {
			$product = $cart_item['data'];
			if ($product->product_type == 'fiztrade') 
				$js_id = $product->dg_id;
			else
				$js_id = 'DM-'. $product->id;
				
			$js_price = $product->get_price('sell') * $cart_item['quantity'];
			$response['lines'][$js_id] = array('price' => woocommerce_price($js_price), 'product' => $product);
		}
	}

	if ($source == 'checkout') {	
		define('WOOCOMMERCE_CHECKOUT', true);
		WC()->cart->calculate_totals();
		
		foreach ($response['lines'] as $key => $info) {		
			$values = array();
			$values['data'] = $info['product'];
			$response['lines'][$key] = apply_filters('woocommerce_checkout_item_subtotal', $info['price'], $values);
			//$response['lines'][$key] = 'orig: '. $info['price'] .' mod: '. apply_filters('woocommerce_checkout_item_subtotal', $info['price'], $values);
		}
		
		$response['subtotal'] = WC()->cart->get_cart_subtotal();
		$response['total'] = WC()->cart->get_total();
		$response['totalUnformatted'] = WC()->cart->total;
	} else if ($source == 'offer-checkout') {
		define('WOOCOMMERCE_OFFER_CHECKOUT', true);
		$offer_cart->calculate_totals();
		
		foreach ($response['lines'] as $key => $info) {		
			$values = array();
			$values['data'] = $info['product'];
			$response['lines'][$key] = apply_filters('woocommerce_offer_cart_item_subtotal', $info['price'], $values);
		}
		
		$response['subtotal'] = $offer_cart->get_cart_subtotal();
		$response['total'] = $offer_cart->get_total();	
		$response['totalUnformatted'] = $offer_cart->cart->total;	
	} else {
		foreach ($response['lines'] as $key => $info) {
			$response['lines'][$key] = $info['price'];
		}
		
		$response['subtotal'] =  woocommerce_price($subtotal);
		$response['total'] =  woocommerce_price($subtotal);
		$response['totalUnformatted'] = $woocommerce->cart->total;
	}

	$response = apply_filters('dm_lock_price', $response);
	
	echo json_encode($response);
	die();
}
add_action('wp_ajax_lock', 'dm_orders_update_lock');

function dm_orders_error() {
	$msg = get_transient('error-'. get_current_user_id());
	if ($msg !== false) {
		?>
		<div class="error">
			<p>Operation failed: <br/><?php echo $msg; ?></p>
		</div>
		<?php
	}
	delete_transient('error-'. get_current_user_id());
}
add_action('admin_notices', 'dm_orders_error' );

// returns true if any items in the order are 
function dm_orders_contains_unordered ($order_items, $selector = 'all') {
	foreach ($order_items as $item_id => $item_data) {	
		$query = new WP_Query( 'post_type=dg_order&orderby=date&order=DESC&post_status=any' );
		$found = 0;
		$open_orders = array();
		while ($query->have_posts()) {
			$p = $query->next_post();
			$dg_order = new DG_Order($p->ID);
			
			// check if this order item is in a DG order
			foreach($dg_order->get_items() as $dg_item_data) {
				if (array_key_exists('_source_item', $dg_item_data['item_meta']) &&
					$dg_item_data['item_meta']['_source_item'][0] == $item_id) {
					// TODO: show status of dg_order
					$temp = $dg_item_data['item_meta']['_qty'][0];
					$found += $temp;
				}
			}	
		}
		
		if ($found < $item_data['item_meta']['_qty'][0]) { // found items not contained in dg_order
			return true;
		}
	}
	return false;
}

function dm_orders_contains_ordered ($order_items) {
	foreach ($order_items as $item_id => $item_data) {	
		$query = new WP_Query( 'post_type=dg_order&orderby=date&order=DESC&post_status=any' );
		$found = 0;
		$open_orders = array();
		while ($query->have_posts()) {
			$p = $query->next_post();
			$dg_order = new DG_Order($p->ID);
			
			// check if this order item is in a DG order
			foreach($dg_order->get_items() as $dg_item_data) {
				if (array_key_exists('_source_item', $dg_item_data['item_meta']) &&
					$dg_item_data['item_meta']['_source_item'][0] == $item_id) {
					// TODO: show status of dg_order
					$temp = $dg_item_data['item_meta']['_qty'][0];
					$found += $temp;
				}
			}	
		}
		
		if ($found > 0) { // found items contained in dg_order
			return true;
		}
	}
	return false;
}

function dm_orders_contains_offered ($offer_items) {
	foreach ($offer_items as $item_id => $item_data) {	
		$query = new WP_Query( 'post_type=dg_offer&orderby=date&order=DESC&post_status=any' );
		$found = 0;
		$open_offers = array();
		while ($query->have_posts()) {
			$p = $query->next_post();
			$dg_offer = new DG_Order($p->ID);
			
			// check if this order item is in a DG order
			foreach($dg_offer->get_items() as $dg_item_data) {
				if (array_key_exists('_source_item', $dg_item_data['item_meta']) &&
					$dg_item_data['item_meta']['_source_item'][0] == $item_id) {
					// TODO: show status of dg_offer
					$temp = $dg_item_data['item_meta']['_qty'][0];
					$found += $temp;
				}
			}	
		}
		
		if ($found > 0) { // found items contained in dg_order
			return true;
		}
	}
	return false;
}

?>
