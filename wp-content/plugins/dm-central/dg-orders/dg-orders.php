<?php
require_once dirname(__FILE__) . '/dg-order-class.php';
require_once dirname(__FILE__) . '/dg-orders-menu.php';

function dm_inv_dg_order_header () {
	if (get_post_type() != 'shop_order')
		return;
		
	echo '<th>DG Order</th>';
}
add_action('woocommerce_admin_order_item_headers', 'dm_inv_dg_order_header');

// adds a column to the shop_order table
function dm_inv_dg_order_column ($product, $item, $item_id) {
	if (get_post_type() != 'shop_order')
		return;
	
	if ($product->product_type != 'fiztrade') {
		echo '<td>&ndash;</td>';
		return;
	}
	
	
	echo '<td>';
	echo dm_inv_get_column_content($item, $item_id);
	
	
	echo '</td>';
}
add_action('woocommerce_admin_order_item_values', 'dm_inv_dg_order_column', 10, 3);

// called when Add to DG Order (on single order page) is clicked
function dm_inv_dg_order_ajax () {
	$item_id = $_POST['itemID'];
	$order_id = $_POST['orderID'];
	$num_items = $_POST['numItems'];
	$dg_order_id = str_replace('dg_order_', '', $_POST['dgOrder']); // may be 'new'
	// echo json_encode(array('newContent' => 'item_id: '. $item_id .' order_id: '. $order_id .' dg_order_id: '. $dg_order_id));
	// die();
	$debug .= 'added '. $num_items;
	$dg_order_id = dm_inv_copy_to_dg_order($item_id, $order_id, $dg_order_id, $num_items);
	
	
	$dg_order = new DG_Order($dg_order_id);
	
	$order = new WC_Order($order_id);
	$items = $order->get_items();
	$new_content = dm_inv_get_column_content($items[$item_id], $item_id);
	
	//$url= admin_url('post.php?post='. $dg_order->id .'&action=edit');
	//$new_content = '<a href="'. $url .'">'. $dg_order->id .'</a>';
	//$new_content = '<span id="found">'. $temp .'</span> in DG Order <a href="'. $url .'">'. $dg_order->id .'</a><br/>';
	//$new_content .= $debug;
	
	$dg_order->update_status( 'processing' );
	
	$result = array (
		'newDGOrder' => $updated_id,
		'newContent' => $new_content 
	);
	
	echo json_encode($result);
	die();
}
add_action('wp_ajax_add_to_dg_order', 'dm_inv_dg_order_ajax');

/**
 * Copies a line item from one order to another,
 * or all FizTrade items in one order to another
 *
 * @param mixed $item_id_in either line item ID or 'all' to copy order items and shipping info
 * @param mixed $source_order WC_Order object or order ID from which to copy the item
 * @param mixed $target_order WC_Order object or order ID to which the item will be copied or 'new' to create a new order
 * @param mixed $num_items quantity of line item to copy or 'all'
 * @param string $target_type post type of target order - used only if target is a new post
 * @return int The post id of the target order
 */
function dm_inv_copy_to_dg_order ($item_id_in, $source_order, $target_order, $num_items = 'all', $target_type = 'dg_order') {
	
	if (!is_object($source_order))
		$source_order = new WC_Order($source_order);
	
	if (!is_object($target_order) && $target_order != 'new')
		$target_order = new DG_Order($target_order);
	
	if ($target_order == 'new') {
		// create new DG order	
		$args = array(
			'post_title' => 'DG Order',  // don't have any better ideas
			//'post_content' => 'Lorem ipsum dolor sit amet...',
			'post_status' => 'publish',
			'post_date' => date('Y-m-d H:i:s'),
			'post_author' => get_current_user_id(),
			'post_type' => $target_type,
			//'post_category' => array(0)
		);
		$id = wp_insert_post($args);
		
		$target_order = new DG_Order($id);
	}
	
	
	$source_items = $source_order->get_items();
	foreach ($source_items as $key => $item) {
		// echo '<pre>';
		// print_r($item);
		$product = get_product($item['product_id']);
		// print_r($product);
		// echo '</pre>';
		// die();
		if ( $product->product_type != 'fiztrade' )
			unset($source_items[$key]);
	}
	
	
	if ($item_id_in == 'all') {
		$to_copy = array_keys($source_items);
		// also copy shipping info		
		$shipping_fields = array(
			'_shipping_method', 
			'_shipping_method_title', 
			'_shipping_first_name',
			'_shipping_last_name',
			'_shipping_company',
			'_shipping_address_1',
			'_shipping_address_2',
			'_shipping_city',
			'_shipping_state',
			'_shipping_postcode',
			'_shipping_country'
		);
			
		$shipping_data = array();		
		foreach ($shipping_fields as $field_name) {
			$shipping_data[$field_name] = get_post_meta($source_order->id, $field_name, true);
		}
		
		// convert shop order shipping method to DG shipping method
		switch ($shipping_data['_shipping_method']) {
			case 'hold':
			case 'store':
				break;
			case 'local_pickup':
				$shipping_data['_shipping_method'] = 'ship_to_me';
				break;
			case 'local_delivery':
			default:
				$shipping_data['_shipping_method'] = dm_inv_get_default_shipping($source_order->user_id);
		}
		
		// save it all
		dm_inv_save_dg_order_meta($target_order->id, $shipping_data);
	} else {
		$to_copy = array($item_id_in);
	}
	
	
	foreach ($to_copy as $item_id) {
		$item = $source_items[$item_id];
		
		$new_item_id = woocommerce_add_order_item( $target_order->id, array('order_item_name' => $item['name']) );
		
		$source_qty = $source_order->get_item_meta( $item_id, '_qty', true );
		// // set quantity
		// if ($num_items != 'all')
			// woocommerce_add_order_item_meta( $new_item_id, '_qty', $num_items, true);
		
		foreach ($source_order->get_item_meta( $item_id ) as $key => $value) {
			if ($key == '_qty' && $num_items != 'all') {
				wc_add_order_item_meta( $new_item_id, '_qty', $num_items);
			} else if (($key == '_line_total' || $key == '_line_subtotal')) {
				// we don't want to show the price here - its going to change anyway
				
				// // get new line total as a fraction of the source order line total
				// $source_lt = $value[0];
				// $target_lt = $num_items / $source_qty * $source_lt; 
				// wc_add_order_item_meta( $new_item_id, $key, $target_lt);
			} else if (substr( $key, 0, 1 ) == '_') { // only get the woocommerce meta fields
				wc_add_order_item_meta( $new_item_id, $key, $value[0]);
				$debug .= $key .' => '. $value[0];
			}
		}
		
		// add metadata so we know where this item came from
		wc_add_order_item_meta( $new_item_id, '_source_order', $source_order->id);
		wc_add_order_item_meta( $new_item_id, '_source_item', $item_id);
		
		//$order_total += $target_order->get_item_meta( $new_item_id, '_line_total', true);
		
		// product stock was reduced when the order was paid, but we're sending it to FizTrade,
		// so put the product stock back where it was
		if ( get_option('woocommerce_manage_stock') == 'yes') {

			if ($item['product_id']>0) {
				$_product = $source_order->get_product_from_item( $item );

				if ( $_product && $_product->exists() && $_product->managing_stock() ) {
					$old_stock = $_product->stock;
					
					if ($num_items == 'all')
						$qty = $item['qty'];
					else $qty = $num_items;

					$new_quantity = $_product->increase_stock( $qty );

					$source_order->add_order_note( sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $item['product_id'], $old_stock, $new_quantity) );

					//$source_order->send_stock_notifications( $_product, $new_quantity, $item['qty'] );
				}

			}

			$source_order->add_order_note( __( 'Items have been moved to a Dillon Gage order, so the local inventory has been increased to reflect this.', 'woocommerce' ) );
		}
	}
	
	// update totals
	// TODO: tax? shipping?
	update_post_meta( $target_order->id, '_order_total', woocommerce_clean( $order_total ) );
	
	return $target_order->id;
}

// returns default DG shipping method (ship_to_me, hold, drop_ship, etc.)
// for user, or site default if user_id not provided
function dm_inv_get_default_shipping ($user_id = null) {
	global $blog_id;
	$debugging = false;
	$options = get_option('imag_mall_options_tech');
	
	$debug .= 'User ID: '. $user_id;

	if (empty($user_id)) {
		$user_meta = array();
		$user_meta['ship_to_consumer'] = null;
	} else {
		$user = get_userdata($user_id);
		$temp = array_map( function ($a) { return $a[0]; }, get_user_meta($user->id));
		
		if (is_multisite()) {
			// filter out shipping settings from other sites
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
	}
	$debug .= '<br/>User meta -> shipping: '. $user_meta['ship_to_consumer'];
	$debug .= '<br/>Site shipping: '. $options['ship_to_consumer'];
	
	if ($debugging)
		wp_die($debug);
		
	if (!empty($user_meta['ship_to_consumer']) && $user_meta['ship_to_consumer'] != 'inherit')
		return $user_meta['ship_to_consumer'];
	else
		return $options['ship_to_consumer'];
}

function dm_inv_get_column_content ($item, $item_id) {

	ob_start();
	$query = new WP_Query( 'post_type=dg_order&orderby=date&order=DESC&post_status=any' );
	$found = 0;
	$open_orders = array();
	while ($query->have_posts()) {
		$p = $query->next_post();
		$dg_order = new DG_Order($p->ID);
		
		// add open DG orders to list
		if ($dg_order->get_status() == 'pending' ||
			$dg_order->get_status() == 'on-hold' ||
			$dg_order->get_status() == 'processing') {
			$open_orders['dg_order_'. $dg_order->id] = $dg_order->id;
		}		
		
		// check if this order item is in a DG order
		foreach($dg_order->get_items() as $dg_item_data) {
			if (array_key_exists('_source_item', $dg_item_data['item_meta']) &&
				$dg_item_data['item_meta']['_source_item'][0] == $item_id) {
				// TODO: show status of dg_order
				$temp = $dg_item_data['item_meta']['_qty'][0];
				//break;
				$url= admin_url('post.php?post='. $dg_order->id .'&action=edit');
				echo '<span class="found">'. $temp .' in DG Order </span><a href="'. $url .'">'. $dg_order->id .'</a><br/>';
				$found += $temp;
			}
		}		
	}
	
	$source_qty = $item['item_meta']['_qty'][0];
	if ($found < $source_qty) {
		// show option to add to any open dg_order
		// or create a new order and add this item to it
		$keys = array_keys($open_orders);
		$default = $keys[0];
		 // the New Order option will be first in the list
		$open_orders = array_merge(array('dg_order_new' => "New"), $open_orders);
		
		echo '<select id="num_items" num="num_items"></select>';
		
		echo '<span style="no-break"><label for="dg_order"> to DG order </label><select id="dg_order" name="dg_order">';
		foreach ($open_orders as $val => $text) {
			echo '<option value="'. $val .'" '. selected($val, $default) .'>'. $text .'</option>';
		}
		echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;</span>';
		
		echo '<button class="button" name="add-item">Add</button>';
		// TODO: fallback
		echo '<noscript>Doesn\'t work without javascript</noscript>';
	}
	
	return ob_get_clean();
}

?>