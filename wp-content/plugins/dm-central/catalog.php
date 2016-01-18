<?php

require_once dirname(dirname(__FILE__)) . '/woocommerce/includes/wc-core-functions.php';

// modify the Woocommerce query with our custom query
function dm_inv_custom_query ($query, $wc_query) {
	$meta_query = $query->get( 'meta_query' );
	$meta_query[] = array(
		'key'     => '_sell_option',
		'value'   => 'no',
		'compare' => '!='
	);
	$meta_query[] = array(
		'key'     => '_buy_option',
		'value'   => 'no',
		'compare' => '!='
	);
	$query->set( 'meta_query', $meta_query );
}
//add_action('woocommerce_product_query', 'dm_inv_custom_query', 10, 2);



// only search visible products
function dm_inv_search_products ($where) {
	if (is_admin() || empty($_GET['s']) || trim(site_url(), '/') == trim(network_site_url(), '/')) 
		return $where;
	else
		return 'AND '. $GLOBALS['wpdb']->posts .'.post_type = "product"'. $where;
}
add_filter('posts_where', 'dm_inv_search_products');

// get the correct number of search results per page
function dm_inv_querysize ($q) {
	if (is_admin() || ! $q->is_main_query() || empty($_GET['s']) || trim(site_url(), '/') == trim(network_site_url(), '/'))
        return;

	// Get a list of post id's which match the current filters set (in the layered nav and price filter)
	$post__in = array_unique( apply_filters( 'loop_shop_post_in', array() ) );
	
	// Meta query
	$meta_query = $q->get( 'meta_query' );
	if ( ! is_array( $meta_query ) )
		$meta_query = array();

	$meta_query[] = WC_Query::visibility_meta_query();
	$meta_query[] = WC_Query::stock_status_meta_query();

	$meta_query = array_filter( $meta_query );

	// Query vars that affect posts shown
	if ( ! $q->is_tax( 'product_cat' ) && ! $q->is_tax( 'product_tag' ) )
		$q->set( 'post_type', 'product' );
	$q->set( 'meta_query', $meta_query );
	$q->set( 'post__in', $post__in );
	$q->set( 'posts_per_page', apply_filters( 'loop_shop_per_page', get_option( 'posts_per_page' ) ) );
}
add_action( 'pre_get_posts', 'dm_inv_querysize', 1 );

// show fiztrade images in archive - overrides woocommerce pluggable function
function woocommerce_get_product_thumbnail_dm_central( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0  ) {
	global $post;
	$product = get_product($post);
	
	if ( has_post_thumbnail() )
		return get_the_post_thumbnail( $post->ID, $size );
	
	if ($product->product_type == 'fiztrade') {
		$image_link  = $product->get_img_url();
		$image_title 		= esc_attr( get_the_title( $post->ID ) );
		
		return sprintf('<img src="%s" class="attachment-shop_catalog wp-post-image" alt="%s">', $image_link, $image_title);
	}
	
	if ( wc_placeholder_img_src() )
		return wc_placeholder_img( $size );
}


// don't show 'Free!' for FizTrade items
function dm_inv_fiztrade_not_free($price, $product) {
	if ($price == 'Free!' && $product->is_type('fiztrade'))
		return 'Price not available';
	else
		return $price;
}
add_filter( 'woocommerce_free_price_html', 'dm_inv_fiztrade_not_free', 20, 2 );

// refresh price buttons
function dm_inv_price_time_update ($productID) {
	
	if (is_archive() || is_search() || is_page_template( 'page-templates/featured.php' )) {					
		if (!woocommerce_products_will_display())
			return;
		
		$plural = 's';
		
	} else { // single product
		global $product;
		
		if ($product->product_type != 'fiztrade' && $product->product_type != 'dealer')
			return;
		
		if ($product->call_for_price('user_buy') && $product->call_for_price('user_sell'))
			return;
		
		if ($product->user_can_buy() && $product->user_can_sell())
			$plural = 's';
		else
			$plural = '';
	}
	?>
	<section id="price-time">
		<span class="timestamp">Price<?php echo $plural; ?> may be out of date.</span>
		<a class="refresh-button" href="">Refresh Price<?php echo $plural; ?></a>
	</section>
	<?php
}
add_filter('woocommerce_single_product_summary', 'dm_inv_price_time_update', 7); // 5 is title, 7 is price
add_action('woocommerce_before_shop_loop', 'dm_inv_price_time_update', 25); // woocommerce_result_count is 20, woocommerce_catalog_ordering is 30

// get single product image(s) from FizTrade
function dm_inv_single_product_images ($image_html, $product_id) {
	if ( has_post_thumbnail() )
		return $image_html; // only do this if dealer hasn't supplied images for this product
	
	$product = get_product($product_id);
	if ($product->product_type != 'fiztrade')
		return $image_html;
	
	$image_link  		= $product->get_img_url();
	$image = '<img src="'. $image_link .'"/>';
	$image_title 		= esc_attr( get_the_title( $post->ID ) );
	
	$obverse_link = $product->get_img_url('obverse');
	$inverse_link = $product->get_img_url('inverse');
	
	if ( $obverse_link != '' ) {
		$gallery = '[product-gallery]';
	} else {
		$gallery = '';
	}
	// $image       = get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), array(
		// 'title' => $image_title
		// ) );

	return sprintf('<a href="%s" itemprop="image" class="woocommerce-main-image zoom" title="%s" data-rel="prettyPhoto' . $gallery . '">%s</a>', $image_link, $image_title, $image );
}
add_filter( 'woocommerce_single_product_image_html', 'dm_inv_single_product_images', 10, 2);

// the add to cart button for fiztrade items
function dm_inv_add_to_cart_button() {
	global $product;
	$cart='bid'; 
	
	if ( ! $product->is_purchasable() ) return;
	if ( ! $product->user_can_buy() ) return;


	// Availability
	$availability = $product->get_availability();

	// if ($availability['availability']) {
		// echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>', $availability['availability'] );
	// }

	if ( !$product->managing_stock() || $product->is_in_stock() || $cart == 'offer' ) {
		$keep_going = true;
		if (function_exists('dm_customers_add_to_cart')) {
			$keep_going = dm_customers_add_to_cart($cart);
		} 
		
		if ($keep_going) { 
			if ($product->call_for_availability('user_buy')) {
				echo apply_filters('dm_filter_call_for_availability', '<span class="add-to-cart-msg">Call for Availability</span>');
				return;
			}

			 do_action( 'woocommerce_before_add_to_cart_form' ); ?>

			<form class="cart" method="post" enctype='multipart/form-data'>
				<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
				<?php
					if ( ! $product->is_sold_individually() )
						woocommerce_quantity_input( array(
							'min_value' => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
							'max_value' => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product )
						) );
				?>

				<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->id ); ?>" />

				<input type="hidden" name="cart" value="<?php echo $cart; ?>" />
				<button type="submit" class="single_add_to_cart_button button alt"><?php echo $cart == 'bid' ? 'Add to Cart' : 'Add to Offer Cart'; ?></button>
				<!--<input type="submit" name="<?php echo $cart; ?>" value="<?php echo $cart == 'bid' ? 'Add to Cart' : 'Add to Offer Cart'; ?>">-->
				<!--<button type="submit" name="cart" value="<?php echo $cart; ?>"><?php echo $cart == 'bid' ? 'Add to Cart' : 'Add to Offer Cart'; //echo $product->single_add_to_cart_text(); ?></button>-->

				<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
			</form>

			<?php do_action( 'woocommerce_after_add_to_cart_form' );
		
		}
	} else {
		echo '<p>Item out of stock at this time. Please try again later.</p>';
	}
}
add_action('woocommerce_fiztrade_add_to_cart', 'dm_inv_add_to_cart_button');
add_action('woocommerce_dealer_add_to_cart', 'dm_inv_add_to_cart_button');

function dm_inv_show_volume_breaks () {
	if (!is_product())
		return;
	
	global $product;
	echo $product->get_volume_breaks_html();
}
add_action('woocommerce_fiztrade_add_to_cart', 'dm_inv_show_volume_breaks');
add_action('woocommerce_dealer_add_to_cart', 'dm_inv_show_volume_breaks');

// adds fields to WooCommerce -> Settings -> General after the Currency field
function dm_inv_add_general_currency_options ($wc_options) {
	$output = array();
	foreach ($wc_options as $option) {
		$output[] = $option;
		
		if ($option['id'] == 'woocommerce_currency') {
			$output[] = array(
				'name'    => __( 'Dollar Format', 'woocommerce' ),
				'desc'    => __( 'This controls whether a currency identifier is shown with a dollar sign.', 'woocommerce' ),
				'id'      => 'woocommerce_replace_dollar',
				//'css'     => 'min-width:150px;',
				'std'     => 'default', // WooCommerce < 2.0
				'default' => 'default', // WooCommerce >= 2.0
				'type'    => 'select',
				'options' => array(
				  'default'        => __( 'No identifier', 'woocommerce' ),
				  'replace'       => __( 'US$', 'woocommerce' ),
				  'replace_space'       => __( 'US $', 'woocommerce' )
				  // TODO?: more formats
				),
				'desc_tip' =>  true,
			);
		}
	}
	return $output;
}
add_filter('woocommerce_general_settings', 'dm_inv_add_general_currency_options');
 
function dm_inv_specify_currency_symbol( $currency_symbol, $currency ) {
	// switch( $currency ) {
		// case 'AUD': $currency_symbol = 'AUD$'; break;
	// }
	$option = get_option('woocommerce_replace_dollar');
	// TODO?: different formats
	if ($option == 'replace' && $currency_symbol == '&#36;')
		return str_replace('D', $currency_symbol, $currency); // $ becomes US$ by replacing the D in USD
	if ($option == 'replace_space' && $currency_symbol == '&#36;')
		return str_replace('D', ' '. $currency_symbol, $currency); // US $
	else
		return $currency_symbol;		
}
add_filter('woocommerce_currency_symbol', 'dm_inv_specify_currency_symbol', 10, 2);

function dm_inv_script_symbol () {
	echo '<script>var currencySymbol = "'. html_entity_decode(get_woocommerce_currency_symbol()) .'";</script>';
}
add_action('wp_head', 'dm_inv_script_symbol');

?>
