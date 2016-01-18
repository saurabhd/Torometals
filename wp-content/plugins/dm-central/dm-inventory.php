<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$host = explode(':', DB_HOST);
define("MYSQLI_HOST", $host[0]);
if (count($host) > 1)
	define("MYSQLI_PORT", $host[1]);

require_once dirname(dirname(__FILE__)) . '/woocommerce/includes/abstracts/abstract-wc-product.php';
require_once dirname(__FILE__) . '/utility.php';
require_once dirname(__FILE__) . '/product-classes.php';
require_once dirname(__FILE__) . '/contango.php';
require_once dirname(__FILE__) . '/product_data_box.php';
require_once dirname(__FILE__) . '/products-table.php';
require_once dirname(__FILE__) . '/catalog.php';
require_once dirname(__FILE__) . '/orders.php';
require_once dirname(__FILE__) . '/charts.php';
require_once dirname(__FILE__) . '/api-wrappers.php';
require_once dirname(__FILE__) . '/dg-orders/dg-orders.php';
require_once dirname(__FILE__) . '/export-orders.php';
require_once dirname(__FILE__) . '/volume-breaks.php';


function dm_inv_script($hook) {
	global $post;
	
	if ($hook == 'post-new.php' || $hook == 'post.php') {
		if ($post->post_type == 'product' || $post->post_type == 'order') {
			wp_register_script('fiztrade-product-admin', plugins_url('/fiztrade-product-admin.js', __FILE__), array('jquery', 'wc-admin-meta-boxes'));//, 'woocommerce_writepanel'
			wp_enqueue_script('fiztrade-product-admin',null, null, null, true);
		}
	}
	
	if ($hook == 'edit.php') {
		if ($_GET['post_type'] == 'shop_order') {
			wp_register_script('fiztrade-order-admin', plugins_url('/fiztrade-order-admin.js', __FILE__), array('jquery'));
			wp_enqueue_script('fiztrade-order-admin',null, null, null, true);
		}
	}
	
	wp_register_script('dm_inv', plugins_url('/dm_inv.js', __FILE__), array('jquery'));
	wp_enqueue_script('dm_inv',null, null, null, true);
	wp_register_script('currency', plugins_url('/jquery.formatCurrency-1.4.0.min.js', __FILE__), array('jquery'));
	wp_enqueue_script('currency',null, null, null, true);
	
	if (is_checkout() || is_product()) {
		wp_register_script('dm_inv_frontend', plugins_url('/dm_inv_frontend.js', __FILE__), array('jquery'));
		wp_enqueue_script('dm_inv_frontend',null, null, null, true);
	}
	
	if (is_admin()) {
		wp_register_style('dm_inv', plugins_url('/inv-styles.css', __FILE__));
		wp_enqueue_style('dm_inv');
	} else {
		wp_register_style('dm_inv', plugins_url('/inv-styles-frontend.css', __FILE__));
		wp_enqueue_style('dm_inv');
	}
	
	// chart stuff - enqueued in wp_footer - if necessary
	wp_register_script('hashtable', plugins_url('/js/jshashtable/jshashtable.js', __FILE__), array());
	wp_register_script('number_formatter', plugins_url('/js/jquery.numberformatter.min.js', __FILE__), array('jquery', 'hashtable'));
	wp_register_script('jqplot', plugins_url('/js/jqplot/jquery.jqplot.min.js', __FILE__), array('jquery'));
	wp_register_script('canvas_ticks', plugins_url('/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js', __FILE__), array('jqplot'));
	wp_register_script('canvas_text', plugins_url('/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js', __FILE__), array('jqplot'));
	wp_register_script('jqplot_cursor', plugins_url('/js/jqplot/plugins/jqplot.cursor.min.js', __FILE__), array('jqplot'));
	wp_register_script('date_axis', plugins_url('/js/jqplot/plugins/jqplot.dateAxisRenderer.min.js', __FILE__), array('jqplot'));
	wp_register_script('jqplot_highlighter', plugins_url('/js/jqplot/plugins/jqplot.highlighter.min.js', __FILE__), array('jqplot'));
	wp_register_script('dm_charts', plugins_url('/charts.js', __FILE__), array('number_formatter', 'canvas_ticks', 'canvas_text', 'jqplot_cursor','date_axis', 'jqplot_highlighter'));
	wp_register_style('jqplot', plugins_url('/js/jqplot/jquery.jqplot.min.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'dm_inv_script');
add_action('wp_enqueue_scripts', 'dm_inv_script');

function dm_inv_footer_enqueue () {
	global $include_chart_js;
	if ($include_chart_js) {
		wp_print_scripts('dm_charts');
	}
}
add_action('wp_footer', 'dm_inv_footer_enqueue');

// define ajaxurl for use on front-end pages
function pluginname_ajaxurl() {
	?>
	<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	</script>
	<?php
}
add_action('wp_head','pluginname_ajaxurl');

// add countdown to checkout
function dm_inv_checkout_countdown () {
	global $offer_cart;
	if (function_exists('is_offer_cart') && is_offer_cart())
		$cart = $offer_cart;
	else
		$cart = WC()->cart;
		
	foreach ($cart->get_cart() as $cart_item) {
		$type = $cart_item['data']->product_type;
		if ($type == 'fiztrade' || $type == 'dealer') {	
			echo '<span class="countdown-area">Stated price good for <span class="dm-countdown">20</span> more seconds</span>';
			echo '<button id="update-price" class="button alt" style="float:right;display:none;">Update Price</button>';
			break;
		}
	}
}
add_action('woocommerce_review_order_before_submit', 'dm_inv_checkout_countdown');

// determine if a product is tradable
function dm_inv_is_tradable($junk, $product) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	// we use these metadata fields instead of the built-in get_price 
	// to determine tradability
	// new trading options
	$sell = get_post_meta($product->id, '_sell_option', true);
	$buy = get_post_meta($product->id, '_buy_option', true);
	
	// old trading options
	$sell = empty($sell) ? get_post_meta($product->id, '_will_sell', true) : $sell;
	$buy = empty($buy) ? get_post_meta($product->id, '_will_buy', true) : $buy;
	$buy = is_plugin_active('dm-offers/dm-offers.php') && $buy;
	if ($sell != 'no' && $buy != 'no') {
		return true;
	} else {
		return false;
	}
}
add_filter('woocommerce_is_purchasable', 'dm_inv_is_tradable', 99, 2);

// if exclude_used is true, only products that haven't been included on this site will be returned
function dm_inv_fiztrade_products($exclude_used = true) {
	global $wpdb;
	// get full product list from central DB
	$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($mysqli->connect_errno) {
		//echo "Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		exit;
	}
	
	// non-optgrop method
	// $products = array();  // will be an array of arrays
	// foreach (array('Gold', 'Silver', 'Platinum', 'Palladium') as $metal) {
		// $table_name = $wpdb->prefix . "products"; 
		// $result = $mysqli->query("SELECT Code, Name FROM $table_name WHERE MetalType='". $metal ."'");
		// $mProducts = array();
		// // construct array of products
		// while ($row = $result->fetch_assoc()) {
			// $mProducts[$row['Code']] = $row['Name'];
		// }
		
		// $products[$metal] = $mProducts;
	// }
	
	$table_name = $wpdb->prefix . "products"; 
	$products = array();  // will be an array of arrays
	foreach (array('Gold', 'Silver', 'Platinum', 'Palladium') as $metal) {
		$result = $mysqli->query("SELECT Code, Name, Description FROM $table_name WHERE MetalType='". $metal ."' AND (IsActiveSell = (1) OR IsActiveBuy = (1)) ORDER BY FamilyID");
		$mProducts = array();
		$currentFamily = '';
		// construct array of products, grouped by family
		while ($row = $result->fetch_assoc()) {
			if ($currentFamily != ucwords(strtolower($row['Description']))) {
				// add a new family to the array
				$currentFamily = ucwords(strtolower($row['Description']));
				$mProducts[$currentFamily] = array();
			}
			// add a new product to a family
			$mProducts[$currentFamily][$row['Code']] = ucwords(strtolower($row['Name']));
		}
		$products[$metal] = $mProducts;
	}
	// the metal types were only used for ordering - collapse them together
	$products = array_merge($products['Gold'], $products['Silver'], $products['Platinum'], $products['Palladium']);
	
	$result->free();
	$mysqli->close();
		
	if ($exclude_used) {
		// get all product posts from this site
		$query = new WP_Query(array(
								'post_type' => 'product',
								'post_status' => 'any',
								'posts_per_page' => -1
							));
							
		while ($query->have_posts()) {
			$query->next_post();
			$productID = get_post_meta($query->post->ID, '_product_id', true);
			foreach ($products as $family => $famProducts) {
				unset($products[$family][$productID]); // remove used product id from list
			}
		}
		wp_reset_postdata();
	}
	return $products;
}

function dm_inv_fiztrade_weight($product_code) {
	global $wpdb;
	// get full product list from central DB
	if (defined('MYSQLI_PORT'))
		$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME, MYSQLI_PORT);
	else
		$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($mysqli->connect_errno) {
		error_log("Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		exit;
	}
	
	$table_name = $wpdb->prefix . "products"; 
	$result = $mysqli->query("SELECT Weight FROM $table_name WHERE Code='$product_code'");
	$row = $result->fetch_assoc();
	
	$output = $row['Weight'];
	
	$result->free();
	$mysqli->close();
	
	return $output;
}

function dm_inv_is_active($product_code, $trade) {
	global $wpdb;
	
	if (empty($product_code))
		return true;
	
	$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($mysqli->connect_errno) {
		//echo "Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		exit;
	}
	
	if ($trade == 'buy')
		$col = 'IsActiveBuy';
	else
		$col = 'IsActiveSell';
	
	$table_name = $wpdb->prefix . "products"; 
	$result = $mysqli->query("SELECT $col FROM $table_name WHERE Code='". $product_code ."'");
	$row = $result->fetch_assoc();

	$output = $row[$col];

	$result->free();
	$mysqli->close();
	
	return $output;
}

// returns true if the category for which the slug is provided has any visible products
function dm_inv_contains_visible_products ($cat_slug) {
	$cat_tax = array(
		'taxonomy' => 'product_cat',
		'field' => 'slug',
		'terms' => array($cat_slug)
	);
	$args = array(
		'tax_query' => array( $cat_tax ),
		'post_type' => 'product',
		'meta_query' => array(
			array( 
				'key' => '_visibility',
				'value' => array('catalog', 'visible'),
				'compare' => 'IN'
			)
		)
	);
	$query = new WP_Query($args);
			
	if ($query->post_count > 0)
		return true;
	else
		return false;
}

// gets spot prices from DB
// $metal must be Gold, Silver, Platinum, or Palladium
function dm_inv_spot($metal) {
	global $spots;
	
	if (!isset($spots)) {
		$spots = dm_api_get('GetSpotPriceData');
	}
	
	if (is_wp_error($spots)) {
		if (is_admin()) {
			WC_Admin_Meta_Boxes::add_error('Error getting spots: '. $spots->get_error_message());
		} else {
			wc_add_notice('Error getting spots: '. $spots->get_error_message(), 'error');
		}
		return $spots;
	}
	
	return array('ask' => $spots[$metal . 'Ask'], 'bid' => $spots[$metal . 'Bid']); // returns ask and bid prices
}

// this will get the spot price delta
// $metal must be Gold, Silver, Platinum, or Palladium
function dm_inv_spot_delta($metal) {
	global $spots;
	
	if (!isset($spots)) {
		$spots = dm_api_get('GetSpotPriceData');
	}
	
	if (is_wp_error($spots)) {
		if (is_admin()) {
			WC_Admin_Meta_Boxes::add_error('Error getting spots: '. $spots->get_error_message());
		} else {
			wc_add_notice('Error getting spots: '. $spots->get_error_message(), 'error');
		}
		return $spots;
	}
	
	return $spots[$metal . 'Change'];
}

// spot price shortcode
// [spot_price metal='gold' formatted=true prices='ask-bid' ajax=false include_delta=false]
function dm_inv_spot_sc ($atts) {
	extract(shortcode_atts( array(
								'metal' => '',  // gold, silver, platinum, or palladium
								'formatted' => true, // includes currency symbol
								'prices' => 'ask-bid', // which prices to include, 'ask', 'bid', or 'ask-bid' - the last implies formatted=true
								'ajax' => false, // if true, updates every other second - implies formatted=true, for now
								'include_delta' => false // includes delta value and up/down arrow
							), $atts));
	if ($metal == '')
		return '';
		
	// undo currency idetifier modifications
	remove_filter('woocommerce_currency_symbol', 'dm_inv_specify_currency_symbol', 10, 2);
	
	$data = dm_inv_spot($metal);
	
	if ($prices == 'ask-bid') {;
		$output = '<span class="spot-ask">' . woocommerce_price($data['ask']) . 
			'</span>/<span class="spot-bid">' .	woocommerce_price($data['bid']) . '</span>';
	} else {
		$output = $data[$prices];	
		if ($formatted == 'true') {
			$output = '<span class="spot-'. $prices .'">' . woocommerce_price($output) . '</span>';
		}
	}

	
	if ($include_delta == 'true') {
		$delta = dm_inv_spot_delta($metal);
		//$imgURL = get_template_directory_uri() . ($delta >= 0 ? '/images/delta-up.png' : '/images/delta-down.png');
		// $output .= sprintf('<img src="%s" alt="+" /><span class="delta-%s">%s</span>', 
			// $imgURL, 
			// $delta >= 0 ? 'positive' : 'negative',
			// sprintf('%.2f', abs($delta)));
		$output .= sprintf('<span class="price-arrow-box %1$s"></span><span class="price-delta %1$s">%2$.2f</span>',
				$delta >= 0 ? 'up' : 'down', abs($delta));				
	}
	
	if ($ajax == 'true') {
		$output = sprintf('<span class="%s-update">%s</span>', $metal, $output);
	}
	
	// re-add currency idetifier modifications
	add_filter('woocommerce_currency_symbol', 'dm_inv_specify_currency_symbol', 10, 2);
	
	return $output;
}
add_shortcode('spot_price_single', 'dm_inv_spot_sc');

// spot prices shortcode - shows all spot prices
// [spot_prices formatted=true prices='ask-bid' include_delta=false]
function dm_inv_spot_all_sc ($atts) {
	extract(shortcode_atts( array(
								'prices' => 'ask-bid', // which prices to include, 'ask', 'bid', or 'ask-bid'
								'include_delta' => true, // includes delta value and up/down arrow
								'include_timestamp' => true // includes clock symbol and timestamp
							), $atts));
		
	ob_start();
	?>
	<div class="ticker-wrapper" style="visibility:hidden">
		<?php if ($include_timestamp) : ?>
		<div id="ticker-aux">
			<div id="ticker-timestamp">
				<div class="overlay"></div>
				<div class="timestamp"></div>
			</div>
		</div>
		<?php endif; ?>
		<?php foreach (array('gold', 'silver', 'platinum', 'palladium') as $metal) : ?>
		<span class="pt-<?php echo $metal; ?> metal-price">
			<strong><?php echo ucfirst($metal); ?>:</strong> 
			<?php
			if ($prices == 'ask' || $prices == 'ask-bid')
				echo '<span class="spot-ask"></span>';
			if ($prices == 'ask-bid')
				echo '/';
			if ($prices == 'bid' || $prices == 'ask-bid')
				echo '<span class="spot-bid"></span>';
			if ($include_delta == 'true')
				echo '<span class="price-arrow-box up"></span><span class="price-delta up"></span>';
			?>
		</span>
		<?php endforeach; ?>
	</div>
	<?php
	$output = ob_get_contents();
	ob_end_clean();
	
	return $output;
}
add_shortcode('spot_prices', 'dm_inv_spot_all_sc');

// price graph shortcode [dm_graph series="all" default_time_domain="3m" width="100%"]
function dm_inv_price_graph_sc ($atts) {
	global $include_chart_js;
	$include_chart_js = true;

	extract(shortcode_atts( array(
								'series' => 'all', // graph what data, 'intraday', 'close', or 'ratio' - 'all' adds a control allowing the user to switch between the three
								'default_time_domain' => '3m', // initial domain of graph - '1h' or '1d' valid for intraday, '1m', '3m', '6m', 'ytd', and '1y' valid for others
								'width' => '100%' // width of graph - any CSS width
							), $atts));
	$active_i = $series == 'intraday' ? 'active' : '';
	$active_c = ($series == 'close' || $series == 'all') ? 'active' : '';
	$active_r = $series == 'ratio' ? 'active' : '';
	$active_1h = $default_time_domain == '1h' ? 'active' : '';
	$active_1d = $default_time_domain == '1d' ? 'active' : '';
	$active_1m = $default_time_domain == '1m' ? 'active' : '';
	$active_3m = $default_time_domain == '3m' ? 'active' : '';
	$active_6m = $default_time_domain == '6m' ? 'active' : '';
	$active_ytd = $default_time_domain == 'ytd' ? 'active' : '';
	$active_1y = $default_time_domain == '1y' ? 'active' : '';
	?>
		<div class="graph-container">
			<div class="btn-group" id="chart-select" style="<?php echo $series != 'all' ? 'display:none' : ''; ?>">
				<a id="chart-intraday" class="dmpilli <?php echo $active_i; ?>">Intraday</a>
				<a id="chart-close" class="dmpilli <?php echo $active_c; ?>">Historical</a>
				<a id="chart-ratio" class="dmpilli <?php echo $active_r; ?>">Ratio</a>
			 </div>
			<div class="btn-group" id="metal-select">
				<a id="chart-gold" class="dmpilli active ">Gold</a>
				<span id="gold-to" style="display:none">Gold to:</span>
				<a id="chart-silver" class="dmpilli">Silver</a>
				<a id="chart-platinum" class="dmpilli">Platinum</a>
				<a id="chart-palladium" class="dmpilli">Palladium</a>
			 </div>
			<div class="btn-group" id="zoom">
				<a id="chart-hour" class="dmpilli <?php echo $active_1h; ?>" style="<?php echo $series != 'intraday' ? 'display:none': ''; ?>">1 Hour</a>
				<a id="chart-day" class="dmpilli <?php echo $active_1d; ?>" style="<?php echo $series != 'intraday' ? 'display:none': ''; ?>">1 Day</a>
				<a id="chart-month" class="dmpilli <?php echo $active_1m; ?>" style="<?php echo $series == 'intraday' ? 'display:none': ''; ?>">1 m</a>
				<a id="chart-3month" class="dmpilli <?php echo $active_3m; ?>" style="<?php echo $series == 'intraday' ? 'display:none': ''; ?>">3 m</a>
				<a id="chart-6month" class="dmpilli <?php echo $active_6m; ?>" style="<?php echo $series == 'intraday' ? 'display:none': ''; ?>">6 m</a>
				<a id="chart-ytd" class="dmpilli <?php echo $active_ytd; ?>" style="<?php echo $series == 'intraday' ? 'display:none': ''; ?>">YTD</a>
				<a id="chart-year" class="dmpilli <?php echo $active_1y; ?>" style="<?php echo $series == 'intraday' ? 'display:none': ''; ?>">1 y</a>
			 </div>
			 <span id="spinnerZoneIntraDay"></span>
			 <div style="width:<?php echo width; ?>" class="chartContent">
			 </div>
		</div>
	
	<?php
}
add_shortcode('dm_graph', 'dm_inv_price_graph_sc');


function dm_inv_get_buy_price($postID) {
	$buyPrice = floatval(get_post_meta($postID, '_buy_price', true ));
	if ($buyPrice != 0) {
		return $buyPrice;
	} else {
		$premiumP = floatval(get_post_meta($postID, '_buy_percent_premium', true ));
		$premiumF = floatval(get_post_meta($postID, '_buy_flat_premium', true ));
		$productID = get_post_meta($postID, '_product_id', true );
		$bid = dm_inv_dg_bid($productID);
		return $bid + $bid * $premiumP / 100 + $premiumF;
	}
}

function dm_inv_get_sell_price($postID) {
	$sellPrice = floatval(get_post_meta($postID, '_sell_price', true ));
	if ($sellPrice != 0) {
		return $sellPrice;
	} else {
		$premiumP = floatval(get_post_meta($postID, '_sell_percent_premium', true ));
		$premiumF = floatval(get_post_meta($postID, '_sell_flat_premium', true ));
		$productID = get_post_meta($postID, '_product_id', true );
		$bid = dm_inv_dg_ask($productID);
		return $bid + $bid * $premiumP / 100 + $premiumF;
	}
}

function dm_inv_get_quote($dg_product_code) {
	global $wpdb;
	
	$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$table_name = $wpdb->prefix . "products"; 
	$result = $mysqli->query("SELECT quote FROM $table_name WHERE code='". $dg_product_code ."'");
	$row = $result->fetch_assoc();
	$result->free();
	return $row['quote'];
}

// add quantity to Add to Cart message
function dm_inv_add_to_cart_qty ($message) {
	$qty = $_POST['quantity'];
	return str_replace('was successfully', sprintf('(x%d) was successfully', $qty), $message);
}
add_filter('woocommerce_add_to_cart_message', 'dm_inv_add_to_cart_qty');

// show current prices on cart page load (and Update Cart)
function dm_inv_cart_live_price () {
	dm_orders_lock_prices();  // the lock_prices function updates the cart values
}
//add_action('woocommerce_cart_loaded_from_session', 'dm_inv_cart_live_price');
//add_action('woocommerce_check_cart_items', 'dm_inv_cart_live_price');
add_action('woocommerce_before_cart', 'dm_inv_cart_live_price');

// show current prices on offer cart page load (and Update Offer Cart)
function dm_inv_cart_live_price_offers () {
	dm_orders_lock_prices(null, 'sell');  // the lock_prices function updates the cart values
}
add_action('woocommerce_before_offer_cart', 'dm_inv_cart_live_price_offers');

function dm_inv_live_price_cleanup () {
	// cancel the price lock
	dm_orders_cancel();
}
add_action('woocommerce_after_cart', 'dm_inv_live_price_cleanup');
add_action('woocommerce_after_offer_cart', 'dm_inv_live_price_cleanup');

// mark fiztrade items on checkout page with their product code
function dm_inv_add_code ($subtotal, $cart_item, $cart_item_key) {
	$product = $cart_item['data'];
	if ($product->product_type == 'fiztrade') {
		return $subtotal .'<span data-product-id="'. $product->dg_id .'"></span>';
	} elseif ($product->product_type == 'dealer') {
		return $subtotal .'<span data-product-id="DM-'. $product->id .'"></span>';
	} else {
		return $subtotal;
	}
}
add_filter('woocommerce_cart_item_subtotal', 'dm_inv_add_code', 20, 3);

// gets the metal type of a given DG product code
function dm_inv_get_metal($code) {
	global $wpdb;
	
	// get product from central DB
	$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($mysqli->connect_errno) {
		//echo "Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		exit;
	}
	
	$table_name = $wpdb->prefix . "products"; 
	$result = $mysqli->query(sprintf("SELECT MetalType FROM $table_name WHERE Code='%s'", $code));
	
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return strtolower($row['MetalType']);
	} else {
		return null;
	}
}

/**************** AJAX callbacks ******************/

function dm_inv_dg_prices_ajax() {
	$productID = $_GET['productID'];
	$tier = $_GET['tier'] ? $_GET['tier'] : 1;
	
	$prices = dm_inv_dg_prices($productID, $tier);
	if (is_wp_error($prices)) 
		echo json_encode(array('',''));
	else
		echo json_encode($prices);
	die();
}
add_action('wp_ajax_dg_prices', 'dm_inv_dg_prices_ajax');

function dm_inv_dg_ask_ajax() {
	$productID = $_GET['productID'];
	$price = dm_inv_dg_ask($productID);
	if (is_wp_error($price)) 
		echo $price->get_error_message();//echo '';
	else
		echo $price;
	die();
}
add_action('wp_ajax_dg_ask', 'dm_inv_dg_ask_ajax');

function dm_inv_dg_bid_ajax() {
	$productID = $_GET['productID'];
	$price = dm_inv_dg_bid($productID);
	if (is_wp_error($price)) 
		echo '';
	else
		echo $price;
	die();
}
add_action('wp_ajax_dg_bid', 'dm_inv_dg_bid_ajax');

function dm_inv_dealer_ask_ajax() {
	$productID = $_GET['productID'];
	$product = get_product($productID); // creating the whole object is a little heavy
									// // may want to pare this down later
	
	if ($product->product_type == 'fiztrade')
		echo $product->get_ask_price_html(true); // true flag instructs it to get price from FizTrade now
	else
		echo $product->get_ask_price_html();
		
	unset($product);
	die();
}
add_action('wp_ajax_ask_price', 'dm_inv_dealer_ask_ajax');
add_action('wp_ajax_nopriv_ask_price', 'dm_inv_dealer_ask_ajax');

function dm_inv_dealer_bid_ajax() {
	$productID = $_GET['productID'];
	$product = get_product($productID); // creating the whole object is a little heavy
									// may want to pare this down later
	
	if ($product->product_type == 'fiztrade')
		echo $product->get_ask_price_html(true); // true flag instructs it to get price from FizTrade now
	else
		echo $product->get_ask_price_html();
		
	unset($product);
	die();
}
add_action('wp_ajax_bid_price', 'dm_inv_dealer_bid_ajax');
add_action('wp_ajax_nopriv_bid_price', 'dm_inv_dealer_bid_ajax');

function dm_inv_retail_prices_ajax() {
	$productID = $_GET['productID'];
	$product = get_product($productID); // creating the whole object is a little heavy
									// may want to pare this down later
	
	$price_info = $product->get_all_prices();
	if (is_wp_error($price_info)) {
		echo json_encode(array('error' => $price_info->get_error_message()));
	} else {
		// don't include private information
		for ($i=1; $i<=count($price_info['tiers']); $i++) {
			unset($price_info['tiers'][$i]['askMargin']);
			unset($price_info['tiers'][$i]['bidMargin']);
			unset($price_info['tiers'][$i]['askCost']);
			unset($price_info['tiers'][$i]['bidCost']);
			unset($price_info['tiers'][$i]['spread']);
		}
	
		echo json_encode($price_info);
	}
	
	unset($product);
	die();
}
add_action('wp_ajax_get_prices', 'dm_inv_retail_prices_ajax');
add_action('wp_ajax_nopriv_get_prices', 'dm_inv_retail_prices_ajax');

function dm_inv_retail_archive_prices_ajax() {
	//ini_set("memory_limit","1200M");
	$fiztrade_products = array();
	$dealer_product_info = array();
	$ft_product_mapping = array();
	foreach ($_GET['productList'] as $product_id) {
		$product = get_product($product_id);
		if ($product->product_type == 'fiztrade') {
			// add to list of fiztrade products to check
			$fiztrade_products[] = $product->dg_id;
			$ft_product_mapping[$product->dg_id] = $product_id;
		} elseif ($product->product_type == 'dealer') {
			$prod_prices = $product->get_all_prices();
			
			$dealer_product_info[] = array(
				'retailProductCode' => $product_id, 
				'tiers' => $prod_prices['tiers']
			);
		}
		unset($product);
	}
	// echo json_encode($ft_product_mapping);
	// die();
	if (count($fiztrade_products))
		$fiztrade_product_info = dm_api_post('GetRetailPrices', $fiztrade_products);
	else
		$fiztrade_product_info = array();
	
	
	if (is_wp_error($fiztrade_product_info)) {
		echo json_encode(array('error' => $fiztrade_product_info->get_error_message()));
		die();
	} else {
		
		// don't include private information
		for ($i=0; $i<count($fiztrade_product_info); $i++) {
			unset($fiztrade_product_info[$i]['DSRPtiers']);
			unset($fiztrade_product_info[$i]['description']); // not private, but is large
			for ($j=1; $j<=count($fiztrade_product_info[$i]['tiers']); $j++) {
				unset($fiztrade_product_info[$i]['tiers'][$j]['askMargin']);
				unset($fiztrade_product_info[$i]['tiers'][$j]['bidMargin']);
				unset($fiztrade_product_info[$i]['tiers'][$j]['askCost']);
				unset($fiztrade_product_info[$i]['tiers'][$j]['bidCost']);
			}
			// add post id back in
			$dg_id = $fiztrade_product_info[$i]['dealerProductCode'];
			$fiztrade_product_info[$i]['retailProductCode'] = $ft_product_mapping[$dg_id];
		}
		$output = array(
			'time' => time(), 
			'product_info' => array_merge($fiztrade_product_info, $dealer_product_info)
		);
	
	}
	
	echo json_encode($output);
	
	die();
}
add_action('wp_ajax_get_archive_prices', 'dm_inv_retail_archive_prices_ajax');
add_action('wp_ajax_nopriv_get_archive_prices', 'dm_inv_retail_archive_prices_ajax');

function dm_inv_update_spot() {
	$metal = $_POST['productID'];
	
	echo do_shortcode('[spot_price metal='.$metal.']');
	die();
}
add_action('wp_ajax_spot', 'dm_inv_update_spot');
add_action('wp_ajax_nopriv_spot', 'dm_inv_update_spot');

function dm_inv_update_spot_and_delta() {
	$metal = $_POST['productID'];
	
	echo do_shortcode('[spot_price metal='.$metal.' include_delta=true]');
	die();
}
add_action('wp_ajax_spot_and_delta', 'dm_inv_update_spot_and_delta');
add_action('wp_ajax_nopriv_spot_and_delta', 'dm_inv_update_spot_and_delta');

function dm_inv_update_ticker() {
	$result = dm_api_get('GetSpotPriceData');
	if (!is_wp_error($result))
		echo json_encode($result);
	die();
}
add_action('wp_ajax_ticker', 'dm_inv_update_ticker');
add_action('wp_ajax_nopriv_ticker', 'dm_inv_update_ticker');

function dm_inv_update_product_data() {
	global $wpdb;
	
	$code = $_GET['code'];
	
	// get product from central DB
	$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($mysqli->connect_errno) {
		//echo "Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		exit;
	}
	
	$table_name = $wpdb->prefix . "products"; 
	$result = $mysqli->query(sprintf("SELECT * FROM $table_name WHERE Code='%s'", $code));
	$output = $result->fetch_assoc();
	//$output['test'] = print_r($_POST, true);
	
	$output = json_encode($output);
	

	echo $output;	
	$result->free();
	$mysqli->close();
	die();
}
add_action('wp_ajax_product_data', 'dm_inv_update_product_data');
