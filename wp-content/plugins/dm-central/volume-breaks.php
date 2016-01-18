<?php
/*
Menu and functions for setting price tiers on volume of product in order
*/

require_once('woocommerce-bulk-discount/woocommerce-bulk-discount.php');

// Add menu page

function dm_inv_vol_break_script($hook) {
	global $post;
	
	if ($hook == 'post-new.php' || $hook == 'post.php') {
		if ($post->post_type === 'product') {
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-button');
			wp_enqueue_script('jquery-ui-datepicker');
			
			wp_register_script('append-grid', plugins_url('/js/jquery.appendGrid-1.1.3.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-button', 'jquery-ui-datepicker'));
			wp_enqueue_script('append-grid',null, null, null, true);
			wp_register_style('append-grid', plugins_url('/js/jquery.appendGrid-1.1.3.css', __FILE__));
			wp_enqueue_style('append-grid');
			
			wp_register_script('vol-breaks', plugins_url('/vol-breaks.js', __FILE__), array('append-grid'));
			wp_enqueue_script('vol-breaks',null, null, null, true);	
		}
	}
}
add_action('admin_enqueue_scripts', 'dm_inv_vol_break_script');

function dm_tiers_add_tab () { ?>
	<li class="volume_breaks_tab volume_breaks_options"><a href="#volume_breaks_data"><?php _e( 'Volume Breaks', 'woocommerce' ); ?></a></li>
<?php }
add_action( 'woocommerce_product_write_panel_tabs', 'dm_tiers_add_tab' );

// dealer version - fiztrade version below - will collapse the two later
function dm_tiers_add_panel() { 
	global $post_id;
	
	// placeholder panel until first save
	if (empty($post_id)) {
		?>
		<div id="volume_breaks_data" class="panel woocommerce_options_panel">
			<div class="options_group">
				<p class="explanation">Please save this product before attempting to set volume breaks.</p>
			</div>
		</div>			
		<?php 
		
		return;
	}
	
	$product = get_product($post_id);
	
	if ($product->product_type == 'fiztrade')
		return;
		
		
		
	?>
	<div id="volume_breaks_data" class="panel woocommerce_options_panel">
		<div class="options_group">
	
			<p class="explanation fiztrade">Below are the tiers for which volume breaks may be specified.  
			The price calculations are same as those on the General tab.  
			The premiums shown next to the range of units/weights will be used if the customer orders that amount of metal.</p>
			<p class="explanation fiztrade">Leaving a row of premiums blank will cause that tier to use the premiums from the previous tier.</p>

			
			<p class="explanation dealer">To add a price tier for your customers, fill in an empty row or click the '+' button to add a new one.  
			Then you may set a range of units/weights at which the volume break applies and the value of the break.</p>
			<p class="explanation dealer">The price calculations are same as those on the General tab.</p>
			
			<label class="dealer"><input type="radio" id="vol-breaks-units" name="_break_type" value="EA" />  Tier by units</label>
			<label class="dealer"><input type="radio" id="vol-breaks-ounces" name="_break_type" value="OZ" />  Tier by fine weight</label>
			<br class="clearfix" />
			
			<table id="vol-breaks">
			</table>
		</div>
	</div>			
	<?php 
	
	if ($product->product_type == 'fiztrade') {
		$metal_type = dm_inv_get_metal($product->dg_id);
	
		// get tiers from fiztrade
		$metal_tiers = dm_api_get('GetRetailPriceTiers', $metal_type);
		
		if (is_wp_error($metal_tiers)) {
			WC_Admin_Meta_Boxes::add_error($metal_tiers->get_error_message() . ' Please avoid making pricing changes until this error no longer appears.');
		} else {		
			// get premiums from fiztrade
			$premiums = $product->get_premiums();
			
			$saved_breaks = array();
			foreach ($metal_tiers as $tier) {
				$saved_breaks[$tier['tier']] = array(
					'units' => $tier['minQty'], 
					'units_high' => $tier['maxQty'],
					'type' => $tier['breakType'],
					'percent_ask' => $premiums[$tier['tier']]['percent_ask'],
					'flat_ask' => $premiums[$tier['tier']]['flat_ask']
				);
			}
			$type = $metal_tiers[0]['breakType'];
			// foreach ($premiums as $tier => $premium) {
				// $saved_breaks[$tier] = array(
					// 'units' => $metal_tiers[$tier - 1]['minQty'] .'-'. $metal_tiers[$tier - 1]['maxQty'], 
					// //'type' => $metal_tiers[$tier]['breakType'],
					// 'percent_ask' => $premium['percent_ask'],
					// 'flat_ask' => $premium['flat_ask']
				// );
			// }
			ksort($saved_breaks);
			
		}
		
		// if fiztrade unavailable
		if (!isset($saved_breaks))
			$saved_breaks = get_post_meta(get_the_ID(), '_volume_breaks', true);
		
		if (empty($saved_breaks))
			$saved_breaks = array(array('units' => '', 'percent-premium' => '', 'flat-premium' => '', 'type' => 'EA'));
		
		if (!isset($type))
			$type = get_post_meta(get_the_ID(), '_break_type', true);	
		
		if (empty($type))
			$type = 'EA';
		?>	
		<!--<input type="hidden" name="break_type" value="<?php echo $type; ?>" />-->
		<script>
			var breaks = <?php echo json_encode(array_values($saved_breaks)); ?>;
			var breakType = '<?php echo $type; ?>';
			var fixedBid = <?php echo $premiums[1]['flat_bid']; // bid premiums are same at all tiers ?>;
			var percentBid = <?php echo $premiums[1]['percent_bid']; ?>;
		</script>
		<?php			
	} else {
		// product type is dealer
		$saved_breaks = get_post_meta(get_the_ID(), '_volume_breaks', true);
		if (empty($saved_breaks))
			$saved_breaks = array(array('units' => '1', 'type' => 'EA', 'percent_ask' => '', 'flat_ask' => ''));	
		
		$type = get_post_meta(get_the_ID(), '_break_type', true);
		if (empty($type))
			$type = 'EA';
	
		?>	
		<script>
			var breaks = <?php echo json_encode(array_values($saved_breaks)); ?>;
			var breakType = '<?php echo $type; ?>';
		</script>
		<?php
	}
	
	
 }
add_action( 'woocommerce_product_write_panels', 'dm_tiers_add_panel' );

// fiztrade version - will collapse the two later
function dm_tiers_calculate_fiztrade_breaks ($post_id_or_code) {
	global $is_dsrp;	
	
	if (is_numeric($post_id_or_code)) {	
		// is a post id
		$post_id = $post_id_or_code;
		
		//$options = get_option('imag_mall_options_tech');
		//$fiz_url = $options['stage_prod'] == 'staging' ? SERVICE_URL_STAGE : SERVICE_URL;
		
		$product = get_product($post_id);	

		$metal_type = dm_inv_get_metal($product->dg_id);
	} else {
		// is a DG product code
		$product_code = $post_id_or_code;
		
		$metal_type = dm_inv_get_metal($product_code);
	}
	

	// get tiers from fiztrade
	$metal_tiers = dm_api_get('GetRetailPriceTiers', $metal_type);
	
	if (is_wp_error($metal_tiers)) {
		WC_Admin_Meta_Boxes::add_error($metal_tiers->get_error_message() . ' Please avoid making pricing changes until this error no longer appears.');
	} else {		
		// get premiums from fiztrade
		if (isset($product_code)) {
			$prem_raw = dm_api_get('GetRetailPremiums', $product_code);			
			$premiums = array();
			if (!empty($prem_raw)) {
				foreach ($prem_raw as $tier => $break) {
					$premiums[$tier] = array(
						'percent_ask' => $break['percentAskDelta'],
						'flat_ask' => $break['fixedAskDelta'],
						'percent_bid' => $break['percentBidDelta'],
						'flat_bid' => $break['fixedBidDelta']			
					);
				}
				
				ksort($premiums);
			}
			
			$prices = dm_api_get('GetRetailPrice', $product_code);
		} else {
			$premiums = $product->get_premiums();
			$prices = dm_api_get('GetRetailPrice', $product->dg_id);
		}
		
		$saved_breaks = array();
		foreach ($metal_tiers as $tier) {
			$saved_breaks[$tier['tier']] = array(
				'dg_ask' => $prices['tiers'][$tier['tier']]['askCost'],
				'dg_bid' => $prices['tiers'][$tier['tier']]['bidCost'],
				'units' => $tier['minQty'], 
				'units_high' => $tier['maxQty'],
				'type' => $tier['breakType'],
				'percent_ask' => $premiums[$tier['tier']]['percent_ask'],
				'flat_ask' => $premiums[$tier['tier']]['flat_ask'],
				'retail_ask' => $prices['tiers'][$tier['tier']]['ask'],
				'percent_bid' => $premiums[$tier['tier']]['percent_bid'],
				'flat_bid' => $premiums[$tier['tier']]['flat_bid'],
				'retail_bid' => $prices['tiers'][$tier['tier']]['bid']
			);
		}
		ksort($saved_breaks);
		
		$type = $metal_tiers[0]['breakType'];
		
		$is_dsrp = true;
		foreach (array_keys($prices['DSRPtiers']) as $key) {
			if ($prices['tiers'][$key]['ask'] != $prices['DSRPtiers'][$key]['ask']) {
				$is_dsrp = false;
				break;
			}
		}
	}
	
	// if fiztrade unavailable
	if (!isset($saved_breaks))
		$saved_breaks = get_post_meta(get_the_ID(), '_volume_breaks', true);
	
	if (empty($saved_breaks))
		$saved_breaks = array(array('units' => '', 'percent-premium' => '', 'flat-premium' => '', 'type' => 'EA'));
	
	if (!isset($type))
		$type = get_post_meta(get_the_ID(), '_break_type', true);	
	
	if (empty($type))
		$type = 'EA';
	
	return array('breaks' => array_values($saved_breaks), 'break_type' => $type);
}

function dm_tiers_include_breaks () {
	global $post_id;
	
	if (empty($post_id)) {
		?>
		<script>
			var breaks = [];
			var breakType = 'EA';
		</script>
		<?php	
	}
	
	$product = get_product($post_id);
	
	if ($product->product_type == 'fiztrade')
		$break_data = dm_tiers_calculate_fiztrade_breaks($post_id);
	else 
		$break_data = array('breaks' => array(), 'break_type' => 'EA'); //TODO
	?>
	
	<input type="hidden" name="_break_type" value="<?php echo $break_data['break_type']; ?>" />
	<script>
		var breaks = <?php echo json_encode($break_data['breaks']); ?>;
		var breakType = '<?php echo $break_data['break_type']; ?>';
		//var fixedBid = <?php echo $premiums[1]['flat_bid']; // bid premiums are same at all tiers ?>;
		//var percentBid = <?php echo $premiums[1]['percent_bid']; ?>;
	</script>
	<?php	
}
add_action('dm_inv_product_panel_fiztrade_sell', 'dm_tiers_include_breaks', 19);

function dm_tiers_add_fiztrade_breaks_sell () { 
	global $is_dsrp;
	
	$options = get_option('imag_mall_options_tech');
	$fiz_url = $options['stage_prod'] == 'staging' ? SERVICE_URL_STAGE : SERVICE_URL;
	?>	
	
	<div class="options_group show_if_fiztrade">
		
		<h4 class="tiers-title">Volume Price Tiers</h4>
		<a class="update-tiers-btn">Update></a>
		
		<table id="vol-breaks-sell">
		</table>
		<p>
		<?php if ($is_dsrp) { echo '<b>Note:</b> Premiums have not been set for this product, so the Dealer Suggested Retail Price is being used.'; }?>
		To edit the premiums, log in to <a href="<?php echo $fiz_url; ?>">FizTrade</a>.
		</p>
		
	</div>

	<?php		
	
 }
add_action('dm_inv_product_panel_fiztrade_sell', 'dm_tiers_add_fiztrade_breaks_sell', 20);

function dm_tiers_add_fiztrade_breaks_buy () { 
	global $is_dsrp;
	
	$options = get_option('imag_mall_options_tech');
	$fiz_url = $options['stage_prod'] == 'staging' ? SERVICE_URL_STAGE : SERVICE_URL;
	?>	
	
	<div class="options_group show_if_fiztrade">
		
		<h4 class="tiers-title">Volume Price Tiers</h4>
		<a class="update-tiers-btn">Update></a>
		
		<table id="vol-breaks-buy">
		</table>
		<p>
		<?php if ($is_dsrp) { echo '<b>Note:</b> Premiums have not been set for this product, so the Dealer Suggested Retail Price is being used.'; }?>
		To edit the premiums, log in to <a href="<?php echo $fiz_url; ?>">FizTrade</a>.
		</p>
		
	</div>

	<?php		
	
 }
add_action('dm_inv_product_panel_fiztrade_buy', 'dm_tiers_add_fiztrade_breaks_buy', 20);

function dm_tiers_get_vol_breaks_ajax () {
	$post_id = $_GET['post_id'];
	
	echo json_encode(dm_tiers_calculate_fiztrade_breaks($post_id));
	die();
}
add_action('wp_ajax_get_vol_breaks', 'dm_tiers_get_vol_breaks_ajax');


function dm_inv_save_vol_breaks ($post_id) {
	global $blog_id;
		
	$break_type = array_key_exists('_break_type', $_POST) ? $_POST['_break_type'] : 'EA';
	$product_type = $_POST['product-type'];
	
	$col_names = array('units', 'percent_ask', 'flat_ask');
	
	$temp = array();
	foreach ($_POST as $key => $value) {
		foreach ($col_names as $col_name) {
			if (strpos($key, $col_name) !== false) {
				$splits = explode('_', $key);
				
				$temp[intval($splits[count($splits)-1])][$col_name] = $value;
			}
		}
	}
	
	// the keys were useful for sorting earlier, but become trouble later
	// remove them
	$breaks = array_values($temp);
	
	if ($product_type == 'fiztrade') { 
		return;
	} else {		
		if (count($breaks) == 0) {
			// first save of this product
			//error_log(print_r($_POST,true));
			
			if ($_POST['_price_option'] == 'flat') {
				if ( !array_key_exists('_sell_price', $_POST) || !is_numeric($_POST['_sell_price']) )
					return;
					
				$percent_ask = 0;
				$flat_ask = $_POST['_sell_price'];			
			} else {				
				if ($_POST['_premium_type'] == 'percent') {
					$percent_ask = $_POST['_sell_spot_premium'];
					$flat_ask = 0;
				} else {
					$percent_ask = 0;
					$flat_ask = $_POST['_sell_price'];
				}
			}			
			
			$breaks = array(array('units' => '1', 'percent_ask' => $percent_ask, 'flat_ask' => $flat_ask));
		}
		
		for ($i=0;$i<count($breaks);$i++) {
			if (!empty($breaks[$i]['units'])) {
				if (empty($breaks[$i]['percent_ask']) && empty($breaks[$i]['flat_ask']))
					return;  // incomplete row, cancel save - UI taken care of in javascript
			}
		
			$breaks[$i]['type'] = $break_type;
		}
		
	}
	
	// save breaks locally
	update_post_meta( $post_id, '_volume_breaks', $breaks );
	update_post_meta( $post_id, '_break_type', $break_type );
	
}
add_action('woocommerce_process_product_meta', 'dm_inv_save_vol_breaks', 15);

// shows the discount on the checkout pane
function dm_inv_calculate_vol_discounts ($totals) {
	if (function_exists('is_offer_checkout')) { // doesn't exist if the dm-offers plugin not loaded
		if (is_offer_checkout())  // not doing this on offer checkout
			return;
	}
	
	global $woocommerce;
	
	$discounts = array();
	foreach ($woocommerce->cart->get_cart() as $cart_item) {
		$product = $cart_item['data'];
		
		$discount = dm_inv_get_discount($product, $cart_item['quantity']);
		if ($discount > 0)
			$discounts[$product->id] = $discount;
	}	
	
	
	global $dm_volume_discounts;
	$dm_volume_discounts = $discounts;
	
	$discount_sum = 0;
	foreach ($discounts as $d) {
		$discount_sum += $d;
	}
	// subtract discount from total
	$totals->cart_contents_total -= $discount_sum;
	return $totals;
 }
//add_action( 'woocommerce_calculate_totals', 'dm_inv_calculate_vol_discounts', 9, 1 );

function dm_inv_vol_break_discount_row_cart () {
	global $woocommerce;
	
	$discounts = array();
	foreach ($woocommerce->cart->get_cart() as $cart_item) {
		$product = $cart_item['data'];
		
		$discount = dm_inv_get_discount($product, $cart_item['quantity']);
		if ($discount > 0)
			$woocommerce->cart->add_fee($product->post->post_title .' Discount', $discount * -1, true);
			//$discounts[$product->id] = $discount;
	}	
}
//add_action('woocommerce_check_cart_items', 'dm_inv_vol_break_discount_row_cart');

function dm_inv_vol_break_discount_row () {
	global $dm_volume_discounts;
	echo 'test:';
	print_r($dm_volume_discounts);
    foreach ($dm_volume_discounts as $post_id => $val) { 
		$post = get_post($post_id);
	?>
		<tr class="payment-extra-charge">
			<th><?php echo $post->post_title; ?> Volume Discount</th>
			<td><?php echo woocommerce_price($val); ?></td>
		</tr>
	<?php }
}
//add_action( 'woocommerce_review_order_before_order_total', 'dm_inv_vol_break_discount_row');



function dm_inv_get_discount($product, $quantity) {	
	if ($product->product_type == 'fiztrade') {
		global $metal_tiers;
		
		// get tiers for this metal
		$metal_type = dm_inv_get_metal($product->dg_id);
		
		if (isset($metal_tiers[$metal_type])) {
			// got them, do nothing
		} else {
			// get tiers from fiztrade
			$mt = dm_api_get('GetRetailPriceTiers', $metal_type);
			
			if (is_wp_error($mt)) {
				error_log($mt->get_error_message() . ' - failed to get tiers.');
				$breaks = array();
			} else {
				$metal_tiers[$metal_type] = array() ;
				foreach ($mt as $ft_tier) {
					$metal_tiers[$metal_type][$ft_tier['tier'] - 1] = array('units' => $ft_tier['minQty'], 'type' => $ft_tier['breakType']);
				}
			}
		}
		$breaks = $metal_tiers[$metal_type];
	} else {
		$breaks = get_post_meta($product->id, '_volume_breaks', true);
	}
	
	uasort($breaks, 'compare_units'); // sort breaks array from highest number of units to lowest
	//echo '<br />DI Breaks:';print_r($breaks);
	foreach ($breaks as $key => $tier_data) {
		if ($tier_data['type'] == 'EA') {
			if ($quantity >= $tier_data['units']) {
				$break_data = array_merge(array('tier' => $key + 1),  $tier_data);	// include which tier this is
				break;
			}
		} else { // weight
			$weight = get_post_meta($product->id, '_product_weight', true);
			$s = explode(' ', $weight);
			$weight = $s[0];
			
			if ($quantity * $weight >= $tier_data['units']) {
				$break_data = array_merge(array('tier' => $key + 1),  $tier_data);	// include which tier this is
				break;
			}
		}
	}
	
	if (empty($break_data)) {
		return 0;
	} else {
		$price_info = $product->get_all_prices();  // getting from API
		$base_price = $price_info['tiers'][1]['ask'];
		$tier_price = $price_info['tiers'][intval($break_data['tier'])]['ask'];
		//echo 'base_price: '. $base_price .'  tier_price: '. $tier_price;
		return ($base_price - $tier_price) * $quantity;
	}	
}

function compare_units($bt1, $bt2) {
	if ($bt1['units'] == $bt2['units'])
		return 0;
	
	return ($bt1['units'] < $bt2['units']) ? 1 : -1;
}

?>
