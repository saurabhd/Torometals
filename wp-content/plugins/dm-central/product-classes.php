<?php
/*
	Adds fiztrade and dealer product types
*/

function dm_inv_class_filter ($classname, $product_type, $post_type, $product_id) {
	return dm_inv_type_to_class($product_type);
}
add_filter('woocommerce_product_class', 'dm_inv_class_filter', 50, 4);

function dm_inv_type_to_class($product_type) {
	if ($product_type == 'fiztrade') {
		return 'WC_Product_FizTrade';
	} else if ($product_type == 'dealer') {
		return 'WC_Product_Dealer';
	} else {
		return $classname;
	}
}

class WC_Product_Dealer extends WC_Product {
	// constants used in display of product
	const MAIN_IMG = 0;
	const OBVERSE_IMG = 1;
	const INVERSE_IMG = 2;
	
	private $ask_price;
	private $bid_price;
	public $breaks;
	private $bulk_discount = 0;
	private $calc_method;
	
	public function __construct( $product ) {
		$this->product_type = 'dealer';
		parent::__construct( $product );
		//$this->price = get_post_meta($product->id, '_sell_price', true);
		//$this->price = $this->__get('sell_price');
		$this->price_option = $this->__get('price_option');
		$this->metal = $this->__get('spot_metal');
		$this->ask_price = $this->__get('sell_price');
		$this->ask_percent_premium = $this->__get('sell_spot_premium');
		$this->bid_price = $this->__get('buy_price');
		$this->bid_percent_premium = $this->__get('buy_spot_premium');
		$this->breaks = $this->__get('breaks');
		
		$weight_splits = explode(' ', $this->__get('product_weight'));
		$weight_str = $weight_splits[0];
		$frac_splits = explode('/', $weight_splits[0]);
		if (count($frac_splits) > 1) {
			$this->weight = $frac_splits[0] / $frac_splits[1];  // calculate fraction value
		}
		else {
			$this->weight = $frac_splits[0];
		}
		
		if ($weight_splits[1] == 'g')
			$this->weight = $this->weight / 31.1033;  // 31.1033 grams in a troy ounce
			
		// calculation method selection
		$this->calc_method = $this->__get('calc_method');
		// may not be set - calc old style
		if (empty($this->calc_method))
			$this->calc_method = 'b';
	}
	
	public function is_purchasable () {
		if ($this->exists() && ($this->user_can_buy() || $this->user_can_sell()))
			return true;
		else
			return false;
	}
	
	public function user_can_buy() {
		$opt = $this->__get('sell_option'); // new
		if (empty($opt))
			$opt = $this->__get('will_sell'); // old
			
		if ($opt != 'no') {
			return true;
		} else {
			return false;
		}
	}
	
	public function user_can_sell() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$opt = $this->__get('buy_option'); // new
		if (empty($opt))
			$opt = $this->__get('will_buy'); // old
			
		if ($opt != 'no' && is_plugin_active('dm-offers/dm-offers.php')) {
			return true;
		} else {
			return false;
		}
	}
	
	public function call_for_price($trade = 'user_buy') {
		if ($trade == 'user_buy')
			$key = 'sell_option';
		else
			$key = 'buy_option';
		
		$opt = $this->__get($key);
		if (!empty($opt))
			$call = $opt == 'callPA' ? true : false;
		else
			$call = $this->__get('call_for_price') == 'yes' ? true : false; // old
		
		
		if ($call)
			return true;
		else
			return false;
	}
	
	public function call_for_availability($trade = 'user_buy') {
		if ($trade == 'user_buy')
			$key = 'sell_option';
		else
			$key = 'buy_option';
		
		$opt = $this->__get($key);
		if (!empty($opt))
			$call = ($opt == 'callA' || $opt == 'callPA') ? true : false;
		else
			$call = $this->__get('call_for_availability') == 'yes' ? true : false; // old
			
		if ($call)
			return true;
		else
			return false;
	}
	
	// in addition to other criteria, either the Buy or Sell box must be checked
	// for the product to show up in the catalog
	public function is_visible() {
		return parent::is_visible() && ($this->user_can_buy() || $this->user_can_sell());
	}

	public function get_ask_price() {
		if ($this->user_can_buy()) {
			if ($this->price_option == 'flat') {
				return $this->ask_price;
			} else {
				$spots = dm_inv_spot($this->metal);
				$spot = $spots['ask'];
				
				if ($this->calc_method == 'a') 
					return round(($spot + $spot * $this->ask_percent_premium / 100 + $this->ask_price) * $this->weight, 2);
				else
					return round($spot * $this->weight + $spot * $this->weight * $this->ask_percent_premium / 100 + $this->ask_price, 2);
			}
		} else {
			return '';
		}
	}
	
	public function get_bid_price() {
		if ($this->user_can_buy()) {
			if ($this->price_option == 'flat') {
				return $this->bid_price;
			} else {
				$spots = dm_inv_spot($this->metal);
				$spot = $spots['bid'];
				
				if ($this->calc_method == 'a') 
					return round(($spot + $spot * $this->bid_percent_premium / 100 + $this->bid_price) * $this->weight, 2);
				else
					return round($spot * $this->weight + $spot * $this->weight * $this->bid_percent_premium / 100 + $this->bid_price, 2);
			}
		} else {
			return '';
		}
	}
	
	public function get_all_prices() {	
				
		$breaks = get_post_meta($this->id, '_volume_breaks', true);
		if ($this->price_option == 'spot') {
			$spots = dm_inv_spot($this->metal);
			
			$price_info = array();					
			$counter = 1;
			foreach ($breaks as $break) {
				if ($this->calc_method == 'a') {
					$ask = round(($spots['ask'] + $spots['ask'] * $break['percent_ask'] / 100 + $break['flat_ask']) * $this->weight, 2);
					$bid = round(($spots['bid'] + $spots['bid'] * $this->bid_percent_premium / 100 + $this->bid_price) * $this->weight, 2);
				} else {
					$ask = round($spots['ask'] * $this->weight + $spots['ask'] * $this->weight * $break['percent_ask'] / 100 + $break['flat_ask'], 2);
					$bid = round($spots['bid'] * $this->weight + $spots['bid'] * $this->weight * $this->bid_percent_premium / 100 + $this->bid_price, 2);
				}
				
				$price_info[$counter] = array('ask' => $ask, 'bid' => $bid, 'units' => $break['units']);			
				$counter++;
			}
			
		} else {  // flat price - may still have volume breaks
			$price_info = array();	
			$counter = 1;
			foreach ($breaks as $break) {
				$price_info[$counter] = array('ask' => $break['flat_ask'], 'bid' => $break['flat_bid'], 'units' => $break['units']);
				$counter++;
			}
		}
		
		if (is_wp_error($price_info))
			return $price_info;
		
		$output = array();
		$output['tiers'] = $price_info;
		$output['time'] = time();
		return $output;
	}
	
	public function get_ask_price_html() {
		if (is_admin()) {
			$price = $this->get_ask_price();
			return $price ? woocommerce_price($price) : 'Price not available';
		} else {
			if ($this->call_for_price('user_buy')) {
				return apply_filters('dm_filter_call_for_price', '<span class="call-for-price">Call for Pricing</span>');
			} else {
				$price = $this->get_ask_price();
				if (empty($price))
					return '';
				
				// if (is_product())
					// return woocommerce_price($price) . $this->get_volume_breaks_html();
				// else
				return woocommerce_price($price);
			}
		}	
	}
	
	public function get_bid_price_html() {
		if (is_admin()) {
			$price = $this->get_bid_price();
			return $price ? woocommerce_price($price) : 'Price not available';
		} else {
			if ($this->call_for_price('user_sell')) {
				return apply_filters('dm_filter_call_for_offer_price', '<span class="call-for-price">Call for Our Current Offer</span>');
			} else {
				$price = $this->get_bid_price();
				return ($price ? woocommerce_price($price) : '');
			}
		}	
	}
	
	public function get_volume_breaks_html() {
		$ask = $this->get_ask_price();
		
		$breaks = get_post_meta($this->id, '_volume_breaks', true);
		
		$type = get_post_meta($this->id, '_break_type', true);
		if ($type == 'EA') {
			$type = 'units';
		} else {
			$weight = explode(' ', get_post_meta($this->id, '_product_weight', true));
			if ($weight[1] == 'oz')
				$type = 'ounces';
			else 
				$type = 'grams';
		}
		
		// collapse tiers with the same premiums
		$disp_breaks = array();
		$disp_breaks[] = $breaks[0];
		for ($i=1; $i<count($breaks); $i++) {
			// collapse tiers if same premiums - tier labels (assigned in js) weren't working
			// if ($breaks[$i]['percent_ask'] == $disp_breaks[count($disp_breaks)-1]['percent_ask'] &&
				// $breaks[$i]['flat_ask'] == $disp_breaks[count($disp_breaks)-1]['flat_ask']) {				
				// $a_units = $disp_breaks[count($disp_breaks)-1]['units'];
				
				// $disp_breaks[count($disp_breaks)-1]['units'] = $a_units;
			// } else {
				// $disp_breaks[] = $breaks[$i];
			// }
			$disp_breaks[] = $breaks[$i];
		}
			
		
		if (count($disp_breaks) > 1 ) {
			$output .= '<section id="volume-breaks"><table>';
			$tier = 2;
			foreach ($disp_breaks as $break) {
				$units = $break['units'];
				if ($units == 1)
					continue;
				
				if ($this->price_option == 'flat')
					$p = $break['flat_ask'];
				else
					$p = $ask + $ask * $break['percent_ask'] / 100 + $break['flat_ask'];		
				
				$output .= "<tr data-tier='$tier'><td>$units $type and up</td><td>". woocommerce_price($p) ."</td></tr>";
				$tier++;
			}
			$output .= '</table></section>';
			
			return $output;
		} else {
			return '';
		}
	}		
	
	public function get_price($trade = 'buy') {
		// bulk discount assigned on cart only
		if (function_exists('is_offer_cart')) {
			if (is_offer_cart() || is_offer_checkout())
				$trade = 'sell';
		}
		
		if ($trade == 'sell')	{	
			return $this->get_bid_price() + $this->bulk_discount;
		} else {
			return $this->get_ask_price() - $this->bulk_discount;
		}
	}
	
	public function set_discount($discount) {
		$this->bulk_discount = $discount;
	}
	
	public function get_price_html() {
		$output = $this->get_ask_price_html();
		$output .= '<span data-product-id="'. $this->id .'"></span>';
		return $output;
	}

}


class WC_Product_FizTrade extends WC_Product {
	// constants used in display of product
	const MAIN_IMG = 0;
	const OBVERSE_IMG = 1;
	const INVERSE_IMG = 2;
	public $breaks;
	public $bulk_discount = 0;
	
	public function __construct( $product ) {
		$this->product_type = 'fiztrade';
		parent::__construct( $product );
		$this->dg_id = $this->__get('product_id');
		$this->ask_flat_premium = $this->__get('sell_flat_premium');
		$this->ask_percent_premium = $this->__get('sell_percent_premium');
		$this->bid_flat_premium = $this->__get('buy_flat_premium');
		$this->bid_percent_premium = $this->__get('buy_percent_premium');
		$this->breaks = $this->__get('breaks');
		
		// database connection
		$this->mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if ($this->mysqli->connect_errno) {
			// TODO: figure out how to write to the main Wordpress log
			//return new WP_Error("SQL_Conn_Err", "Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error, $fd);
		}
		
		$this->price = $this->get_quote();
	}
	
	public function is_purchasable () {
		if ($this->exists() && ($this->user_can_buy() || $this->user_can_sell()))
			return true;
		else
			return false;
	}
	
	public function user_can_buy() {
		$opt = $this->__get('sell_option'); // new
		if (empty($opt))
			$opt = $this->__get('will_sell'); // old
			
		if ($opt != 'no') {
			return true;
		} else {
			return false;
		}
	}
	
	public function user_can_sell() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$opt = $this->__get('buy_option'); // new
		if (empty($opt))
			$opt = $this->__get('will_buy'); // old
			
		if ($opt != 'no' && is_plugin_active('dm-offers/dm-offers.php')) {
			return true;
		} else {
			return false;
		}
	}
	
	public function call_for_price($trade = 'user_buy') {
		if ($trade == 'user_buy')
			$key = 'sell_option';
		else
			$key = 'buy_option';
		
		$opt = $this->__get($key);
		if (!empty($opt))
			$call = $opt == 'callPA' ? true : false;
		else
			$call = $this->__get('call_for_price') == 'yes' ? true : false; // old
		
		
		if ($call)
			return true;
		else
			return false;
	}
	
	public function call_for_availability($trade = 'user_buy') {
		if ($trade == 'user_buy')
			$key = 'sell_option';
		else
			$key = 'buy_option';
		
		$opt = $this->__get($key);
		if (!empty($opt))
			$call = ($opt == 'callA' || $opt == 'callPA') ? true : false;
		else
			$call = $this->__get('call_for_availability') == 'yes' ? true : false; // old
		
		
		if ($call)
			return true;
		else
			return false;
	}
	
	// in addition to other criteria, either the Buy or Sell box must be checked
	// for the product to show up in the catalog
	public function is_visible() {
		// echo strval($this->dg_id);
		// echo parent::is_visible() && ($this->user_can_buy() || $this->user_can_sell()) ? 'yes ' : 'no ';
		return parent::is_visible() && ($this->user_can_buy() || $this->user_can_sell());
	}
	
	// gets the proper source url from database
	// $which may also be obverse or inverse
	public function get_img_url($which = 'image') {		
		global $wpdb;
		
		$table_name = $wpdb->prefix . "products"; 
		$result = $this->mysqli->query("SELECT ImagePath, $which FROM $table_name WHERE code='". $this->dg_id ."'");
		$row = $result->fetch_assoc();
		$result->free();
		if ($row[$which] != '')
			return $row['ImagePath'] .'/'. $row[$which];
		else
			return '';
	}
	
	// gets the quote price, which is updated by cron
	public function get_quote() {	
		global $wpdb;
		
		$table_name = $wpdb->prefix . "products"; 
		$result = $this->mysqli->query("SELECT quote FROM $table_name WHERE code='". $this->dg_id ."'");
		$row = $result->fetch_assoc();
		$result->free();
		return $row['quote'];
	}
	
	// gets the quote price, or the locked price if there is one
	public function get_dg_price() {
		$lock = get_transient('lock-'. get_current_user_id());
		if ($lock !== false) { 
			// if this product has has a price locked, return the locked price
			foreach($lock['prices'] as $price_info) {
				if ($price_info['product'] == $this->dg_id) {
					$dg_price = $price_info['price'];
					break;
				}
			}
		}
		// there's no lock for this product
		$dg_price = !empty($dg_price) ? $dg_price : $this->get_quote();
		return $dg_price;
	}
	
	// gets the dealer's asking price including the dealer markup
	public function get_ask_price($updateNow = false) {
		global $cart_prices;
		
		if (!$this->user_can_buy()) {
			return null;
		} else {					
			// getting DG price and dealer premiums from fiztrade
			$price_info = $this->get_all_prices();
			
			if (is_wp_error($price_info)) {
				$dg_ask = $this->get_dg_price();
				return $dg_ask + $dg_ask * $this->ask_percent_premium / 100 + $this->ask_flat_premium;
			} else {
				return $price_info['tiers']['1']['ask'];
			}
		}
	}
	
	// gets the dealer's bidding price including the dealer markup
	public function get_bid_price($updateNow = false) {
		if (!$this->user_can_sell()) {
			return null;
		} else {				
			// getting DG price and dealer premiums from fiztrade
			$price_info = $this->get_all_prices();
			
			if (is_wp_error($price_info)) {
				$dg_bid = $this->get_dg_price();
				return $dg_bid + $dg_bid * $this->bid_percent_premium / 100 + $this->bid_flat_premium;
			} else {
				return $price_info['tiers']['1']['bid'];
			}
		}
	}
	
	public function get_all_prices() {
		global $ft_prices;
		
		if (isset($ft_prices[$this->dg_id])) {
			// already got price for this item in this request
			//error_log($this->dg_id . ' price from group get');
		} else {
			//error_log($this->dg_id . ' price from individual get');
			$price_info = dm_api_get('GetRetailPrice', $this->dg_id);
			if (is_wp_error($price_info))
				return $price_info;
			
			$ft_prices[$this->dg_id] = $price_info['tiers'];
		}
		
		$output = array();
		$output['tiers'] = $ft_prices[$this->dg_id];
		$output['time'] = time();
		return $output;
	}
	
	// formatting methods	
	public function get_price_html() {
		return $this->get_ask_price_html(true) . '<span data-product-id="'. $this->id .'"></span>';
	}
	
	public function get_ask_price_html($updateNow = false) {
		if (is_admin()) {
			$price = $this->get_ask_price($updateNow);
			return $price ? woocommerce_price($price) : 'Price not available';
		} else {
			if ($this->call_for_price('user_buy')) {
				return apply_filters('dm_filter_call_for_price', '<span class="call-for-price">Call for Pricing</span>');
			} else {
				$price = $this->get_ask_price($updateNow);
				return ($price ? woocommerce_price($price) : '');
			}
		}		
	}
	
	public function get_bid_price_html($updateNow = false) {
		if (is_admin()) {
			$price = $this->get_bid_price($updateNow);
			return $price ? woocommerce_price($price) : 'Price not available';
		} else {
			if ($this->call_for_price('user_sell')) {
				return apply_filters('dm_filter_call_for_price', '<span class="call-for-price">Call for Our Current Offer</span>');
			} else {
				$price = $this->get_bid_price($updateNow);
				return ($price ? woocommerce_price($price) : '');
			}
		}	
	}
	
	public function get_volume_breaks_html() {
		global $metal_tiers;
		
		// get tiers for this metal
		$metal_type = dm_inv_get_metal($this->dg_id);
		
		if (isset($metal_tiers[$metal_type])) {
			// got them, do nothing
		} else {
			// get tiers from fiztrade
			$mt = dm_api_get('GetRetailPriceTiers', $metal_type);
			
			if (is_wp_error($mt)) {
				log_error($mt->get_error_message() . ' - failed to get tiers.');
			} else {
				$metal_tiers[$metal_type] = array() ;
				foreach ($mt as $ft_tier) {
					$metal_tiers[$metal_type][$ft_tier['tier']] = array('units' => $ft_tier['minQty'], 'type' => $ft_tier['breakType']);
				}
			}
		}
		
		$response = dm_api_post('GetRetailPrices', array($this->dg_id));
		if (is_wp_error($response)) {
			log_error($response->get_error_message() . ' - failed to get retail prices.');
			return;
		}
		
		$prices = $response[0]['tiers'];
		// get DSRP if premiums not set
		if (empty($prices))
			$prices = $response[0]['DSRPtiers'];
		
		$breaks = array();
		for ($i=1; $i<=4; $i++) {
			$breaks[] = array(
							'units' => $metal_tiers[$metal_type][$i]['units'],
							'price' => $prices[$i]
						);
		}
		
		
		$type = $metal_tiers[$metal_type][1]['type'] == 'EA' ? 'units' : 'ounces';
		
		// sort breaks by units
		usort($breaks, function ($a, $b) {
			return $a['units'] <= $b['units'] ? -1 : 1;
		});
		
		// collapse tiers with the same premiums
		$disp_breaks = array();
		$disp_breaks[] = $breaks[0];
		for ($i=1; $i<count($breaks); $i++) {
			// collapse tiers if same premiums - tier labels (assigned in js) weren't working
			// if ($breaks[$i]['percent_ask'] == $disp_breaks[count($disp_breaks)-1]['percent_ask'] &&
				// $breaks[$i]['flat_ask'] == $disp_breaks[count($disp_breaks)-1]['flat_ask']) {				
				// $a_units = $disp_breaks[count($disp_breaks)-1]['units'];
				
				// $disp_breaks[count($disp_breaks)-1]['units'] = $a_units;
			// } else {
				// $disp_breaks[] = $breaks[$i];
			// }
			$disp_breaks[] = $breaks[$i];
		}
			
		
		if (count($disp_breaks) > 1 ) {
			$output .= '<section id="volume-breaks"><table>';
			$tier = 2; // volume break tier added for AJAX update to table;
			foreach ($disp_breaks as $break) {
				$units = $break['units'];
				if ($units == 1)
					continue;
									
				$output .= "<tr data-tier='$tier'><td>$units $type and up</td><td>". woocommerce_price($break['price']) ."</td></tr>";
				$tier++;
			}
			$output .= '</table></section>';
			
			return $output;
		} else {
			return '';
		}
	}
		
	
	public function get_price($trade = 'buy') {
		// bulk discount assigned on cart only
		if (function_exists('is_offer_cart')) {
			if (is_offer_cart() || is_offer_checkout())
				$trade = 'sell';
		}
		
		if ($trade == 'sell')	{	
			return $this->get_bid_price() + $this->bulk_discount;
		} else {
			return $this->get_ask_price() - $this->bulk_discount;
		}
	}
	
	public function set_discount($discount) {
		$this->bulk_discount = $discount;
	}
	
	/// publish premiums to FizTrade
	/// will cancel product save if publish fails
	/// note that premiums are also saved locally, but not in this method
	public function set_premiums($tier_premiums)  {
		$debugging = false;
		$n_tiers = 4;
		// TODO: test if premiums have changed
		
		// if fewer than maximum number of tiers supplied,
		// copy the last tier for padding
		if (count($tier_premiums) < $n_tiers)
			$tier_premiums = array_fill(count($tier_premiums), $n_tiers - count($tier_premiums), $tier_premiums[count($tier_premiums) - 1]);
		
		$ft_tiers = array();
		$counter = 1;
		foreach ($tier_premiums as $tier) {
			$ft_tiers[] = array(
				'dealerProductCode' => strval($this->id),
				'dillonGageProductCode' => $this->dg_id,
				'tier' => $counter,
				'referenceTier' => $counter,
				'percentAsk' => $tier['percent_ask'],
				'fixedAsk' => $tier['flat_ask'],
				'percentBid' => $tier['percent_bid'],
				'fixedBid' => $tier['flat_bid'],
			);
			$counter++;
		}
		
		$result = dm_api_post('SetRetailPremiums', $ft_tiers);
		
		if ($debugging) {
			WC_Admin_Meta_Boxes::add_error('SetRetailPremiums: '. print_r($ft_tiers, true));
			WC_Admin_Meta_Boxes::add_error('Response: '. print_r($result, true));
		}
		
		// check for errors
		if (is_wp_error($result))
			return $result;
			
		foreach ($result as $tier_res) {
			if ($tier_res['status'] != 'ok')
				return new WP_Error('set_premiums_failed', 'Failed to set premiums. Please report this error.');
		}
		
		// all good
		return 0;
	}
	
	/// get premiums from FizTrade
	public function get_premiums()  {
		$result = dm_api_get('GetRetailPremiums', $this->dg_id);
		
		// check for errors
		if (is_wp_error($result))
			return $result;
		
		$output = array();
		if (!empty($result)) {
			foreach ($result as $tier =>$break) {
				$output[$tier] = array(
					'percent_ask' => $break['percentAskDelta'],
					'flat_ask' => $break['fixedAskDelta'],
					'percent_bid' => $break['percentBidDelta'],
					'flat_bid' => $break['fixedBidDelta']			
				);
			}
			
			ksort($output);
			return $output;
		} else {
			return array();
		}
	}
	
	public function __destruct() {
		$this->mysqli->close();
	}
}
