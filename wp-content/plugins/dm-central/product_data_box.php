<?php
/*
	This customizes the Product Data metabox shown on the New/Edit Product screen.
*/

// select box of types of products
function dm_inv_product_types($types) {	
	$types['fiztrade'] = 'FizTrade Product';
	$types['dealer'] = 'Spot & Premium Product';
	return $types;
}
add_filter('product_type_selector', 'dm_inv_product_types');

// Buy/Sell checkboxes
function dm_inv_type_options() {
	$options = array(
		'will_sell' => array(
				'id' => 'chkSell',
				'wrapper_class' => '',
				'label' => 'Sell',
				'description' => 'List product for sale on the site.'
			),
		'will_buy' => array(
				'id' => 'chkBuy',
				'wrapper_class' => '',
				'label' => 'Buy',
				'description' => 'Accept offers of this product.'
			),
		'call_for_availability' => array(
				'id' => 'chkCallA',
				'wrapper_class' => '',
				'label' => 'Call for Availability',
				'description' => 'Instead of an Add to Cart button, customers will see "Call for Availability" when this is checked.'
			),
		'call_for_price' => array(
				'id' => 'chkCall',
				'wrapper_class' => '',
				'label' => 'Call for Pricing',
				'description' => 'Instead of a price, customers will see "Call for Price" when this is checked.'
			)
	);
	// hiding all these - are set by radio buttons below
	?>
	<style>
	#woocommerce-product-data .type_box .tips { display:none; }
	</style>
	<?php
	
	if (!is_plugin_active('dm-offers/dm-offers.php'))
		unset($options['will_buy']);
	
	return $options;
}
add_filter('product_type_options', 'dm_inv_type_options');

// pricing
function dm_inv_pricing() {
	global $thepostid;
	
	woocommerce_wp_hidden_input( array(
		'id' => 'editing',
		'value' => $_GET['action'] == 'edit' ? 'edit' : 'new'
	));

	// Dealer Items ?>
	<div class="options_group pricing show_if_dealer">
		<?php	
		$weight_str = get_post_meta(get_the_ID(),'_product_weight', true);
		if (!empty($weight_str)) {
			$weight = explode(' ', $weight_str);
			$weight_val = $weight[0];
			$weight_unit = $weight[1];
		}
		?>
		<p class="form-field">
			<label for="_product_weight_dealer">Fine Weight</label>
			<input type="text" id="_product_weight" name="_product_weight_dealer" class="short" value="<?php echo $weight_val; ?>" />
			<select id="weight_unit" name="weight_unit">
				<option value="oz" <?php selected($weight_unit, 'oz'); ?>>oz </option>
				<option value="g" <?php selected($weight_unit, 'g'); ?>>g </option>
			</select>
		</p>
		<?php
		
		// removing price options from panel
		// TODO: remove hidden field after all price option references removed.
		woocommerce_wp_hidden_input( array(
			'id' => '_price_option',
			'value' => 'spot'
		));
		
		imaginuity_wp_select(array(
			'id' => '_spot_metal',
			'label' => 'Select Spot',
			'wrapper_class' => 'spot-only',
			'class' => 'select short',
			'options' => array('gold' => 'Gold', 'silver' => 'Silver', 'platinum' => 'Platinum', 'palladium' => 'Palladium')
		));
		
		$flat = get_post_meta(get_the_ID(), '_sell_price', true);
		if ($flat != '') 
			$prem = 'flat';
		else
			$prem = 'percent';
		woocommerce_wp_radio( array(
			'id' => '_premium_type',
			'wrapper_class' => 'radio-horiz spot-top',
			'options' => array( 'percent' => 'Use a percentage premium', 'flat' => 'Use a flat premium'),
			'value' => $prem
		) );
		
		$calc_method = get_post_meta(get_the_ID(), '_calc_method', true);
		if (empty($calc_method))
			$calc_method = 'b';
		imaginuity_wp_select(array(
			'id' => '_calc_method',
			'label' => 'Calculation Method',
			'wrapper_class' => 'spot-top',
			'class' => 'select',
			'options' => array('a' => 'A', 'b' => 'B'), // formulas added by javascript - fiztrade-product-admin.js
			'value' => $calc_method
		));
	
		$view = get_post_meta(get_the_ID(), '_price_option', true);
			
		$metal = get_post_meta(get_the_ID(), '_spot_metal', true);
		if ($metal == '')
			$metal = 'gold';
			
		$spots = dm_inv_spot($metal);
		
		if (is_wp_error($spots)) {
			$hide = '';
			$spots = array('ask' => '', 'bid' => '');
		} else {
			$hide = 'style="display:none"';
		}
		?>
		<div id="price-error" class="ft-error" <?php echo $hide; ?> >Couldn't retrieve spots. Please try again.</div>
		
	</div>
	
	<div class="options_group show_if_fiztrade clearfix">
		<div id="ft-opts">
			<?php
			$ft_products = $_GET['action'] == 'edit' ? dm_inv_fiztrade_products(false) : dm_inv_fiztrade_products();
			// load selected product - does nothing for a new post
			$productID = '';
			$product = get_product(get_the_ID());
			if ( $product->product_type == 'fiztrade') {
				$productID = $product->dg_id;
				
				$prices = $productID != '' && isset($ft_products[$productID]) ? dm_inv_dg_prices($productID) : array('ask' => '', 'bid' => '');
				
				if (is_wp_error($prices)) {
					$hide = '';
					$prices = array('ask' => '', 'bid' => '');
				} else {
					$hide = 'style="display:none"';
				}
			} else {
				$hide = 'style="display:none"';
				$prices = array('ask' => '', 'bid' => '');
			}
			
			imaginuity_wp_select(array(
				'id' => '_product_id',
				'label' => 'Select FizTrade Product',
				'class' => 'select', //. ($_GET['action'] == 'edit' ? ' disable-me' : ''),
				'options' => array_merge(array('' => ''), $ft_products)
			));
			?>
			<p class="form-field">
				<label>Copy Product Desc. Here</label>
				<button id="copy-desc">Copy</button>
			</p>
			
			<?php $prod_weight = !empty($productID) ? dm_inv_fiztrade_weight($productID) : ''; ?>
			<p class="form-field">
				<label for="_product_weight_fiztrade">Fine Weight</label>
				<input type="text" id="_product_weight_fiztrade" name="_product_weight_fiztrade" class="short" readonly="readonly" value="<?php echo $prod_weight; ?>" />
			</p>
			<br/>
			<p>
			This product photo will be used on the front end unless a Featured Image is supplied in the box to the right.
			</p>
		</div>
		<?php
		$product = get_product(get_the_ID());
		if ($product instanceof WC_Product_FizTrade) : ?>
		
		<img id="gallery" src="<?php echo $product->get_img_url() ?>"/>
		
		<?php else : ?>
		
		<img id="gallery" style="display:none"/>
		
		<?php endif; ?>

	</div>
	
	
	<div class="options_group pricing show_if_fiztrade show_if_dealer">
		<h4>Sell</h4>
		
		<?php		
		$active_sell = dm_inv_is_active($productID, 'sell');	

		if (!$active_sell)
			echo '<p>This product not for sale through FizTrade at this time.</p>';
		
		$sell_opt = get_post_meta(get_the_ID(),'_sell_option', true);
		// setting with javascript until we're sure that no old-style items here
		// if (empty($sell_opt))
			// $sell_opt = 'no';
			
		woocommerce_wp_radio( array(
			'id' => 'sell_option',
			'options' => array( 
				'no' => 'Don\'t sell this product', 
				'yes' => 'Enable selling to customer',
				'callA' => 'Display "Call for Availability" (price displayed)',
				'callPA' => 'Display "Call for Pricing and Availability"'
			),
			'value' => $sell_opt
		) );
		?>
		<script>
			jQuery(document).ready(function ($) {
				// isActiveSell
				if('<?php echo $active_sell ? 'active' : 'inactive'; ?>' == 'inactive') {
					$('.sell_option_field input:not([value="no"])').prop('disabled', true);
					$('.sell_option_field input[value="no"]').prop('checked', true);
				}
			});
		
		</script>	
			
		<div class="show_if_dealer">
			<?php
			// Premiums
			
			woocommerce_wp_text_input( array(
				'id' => 'spot-ask',
				'wrapper_class' => 'spot-only',
				'class' => 'wc_input_price short',
				'label' => ucfirst($metal) .' Spot',
				'type' => 'number',
				'value' => $spots['ask'],
				'custom_attributes' => array( 'disabled' => 'true' )
			));
			
			echo '<div class="update-btn spot-only"><a class="update-spot">Update></a></div>';
			//echo '<button id="update-ask" onclick="updateAsk" style="position:absolute;top:139px;left:450;">Update</button>';
			
			woocommerce_wp_text_input( array( 
				'id' => '_sell_price', 
				'class' => 'wc_input_price short sell', 
				'label' => ($view == 'spot' ? 'Flat Premium' : 'Sell Price') . ' ('.get_woocommerce_currency_symbol().')', 
				'type' => 'number', 
				'custom_attributes' => array(
					'step' 	=> 'any'
				) 
			) );
			woocommerce_wp_text_input( array( 
				'id' => '_sell_spot_premium', 
				'wrapper_class' => 'spot-only',
				'class' => 'wc_input_price short sell', 
				'label' => 'Percentage Premium (%)', 
				'type' => 'number', 
				'custom_attributes' => array(
					'step' 	=> 'any'
				) 
			) );
			echo '<p class="spot-only">Retail price:  <span id="spot-sell-price"></span></p>';
			?>
		</div>
		
		<div class="show_if_fiztrade">			
			<?php do_action('dm_inv_product_panel_fiztrade_sell'); ?>
		</div>
	</div>
	
	<?php if (is_plugin_active('dm-offers/dm-offers.php')) : ?>
		
	<div class="options_group pricing show_if_fiztrade show_if_dealer">
		<h4>Buy</h4>
		
		<?php		
		$active_buy = dm_inv_is_active($productID, 'buy');		
		if (!$active_buy)
			echo '<p>FizTrade not accepting offers of this product at this time.</p>';
			
		$buy_opt = get_post_meta(get_the_ID(),'_buy_option', true);
		// setting with javascript until we're sure that no old-style items here
		// if (empty($sell_opt))
			// $buy_opt = 'no';
			
		woocommerce_wp_radio( array(
			'id' => 'buy_option',
			'options' => array( 
				'no' => 'Don\'t buy this product', 
				'yes' => 'Enable buying from customer',
				'callA' => 'Display "Call to Make an Offer" (price displayed)',
				'callPA' => 'Display "Call for Our Current Offer"'
			),
			'value' => $buy_opt
		) );	
		?>
		<script>
			jQuery(document).ready(function ($) {
				// isActiveBuy
				if('<?php echo $active_buy ? 'active' : 'inactive'; ?>' == 'inactive') {
					$('.buy_option_field input:not([value="no"])').prop('disabled', true);
					$('.buy_option_field input[value="no"]').prop('checked', true);
				}
			});
		
		</script>
		
		<div class="show_if_dealer">
			<?php
			woocommerce_wp_text_input( array(
				'id' => 'spot-bid',
				'wrapper_class' => 'spot-only',
				'class' => 'wc_input_price short',
				'label' => ucfirst($metal) .' Spot',
				'type' => 'number',
				'value' => $spots['bid'],
				'custom_attributes' => array( 'disabled' => 'true' )
			));
			
			echo '<div class="update-btn spot-only"><a class="update-spot">Update></a></div>';
			
			woocommerce_wp_text_input( array( 
				'id' => '_buy_price', 
				'class' => 'wc_input_price short buy', 
				'label' => ($view == 'spot' ? 'Flat Premium' : 'Buy Price') . ' ('.get_woocommerce_currency_symbol().')', 
				'type' => 'number', 
				'custom_attributes' => array(
					'step' 	=> 'any'
				) 
			) );
			woocommerce_wp_text_input( array( 
				'id' => '_buy_spot_premium', 
				'wrapper_class' => 'spot-only',
				'class' => 'wc_input_price short buy', 
				'label' => 'Percentage Premium (%)', 
				'type' => 'number', 
				'custom_attributes' => array(
					'step' 	=> 'any'
				) 
			) );
			// TODO: might think about putting in a buy/sell price calculation
			// fallback in case javascript isn't working
			echo '<p class="spot-only">Retail price:  <span id="spot-buy-price"></span></p>';
			?>
			
		</div>
		
		<div class="show_if_fiztrade">				
			<?php do_action('dm_inv_product_panel_fiztrade_buy'); ?>
		</div>
	</div>
	<?php endif; 
}
add_action('woocommerce_product_options_general_product_data', 'dm_inv_pricing');

// inventory tab
function dm_inv_inventory_note() {
	?>
	<p id="ft-inv-note">
		If automatic order forwarding is enabled and local inventory is present,
		orders will be filled from local inventory instead of being forwarded.
		If an order is placed that requires more stock than is in local inventory,
		the entire order is forwarded to FizTrade.'.
		<br/><br/>
		Setting 'Allow Backorders?' to 'Do not allow' will disable this functionality, 
		and customers will not be able to order this product if Stock Qty is less than one.
	</p>
	<?php
}
add_action('woocommerce_product_options_stock_fields', 'dm_inv_inventory_note');

// save FizTrade product
function dm_inv_save_fiztrade($post_id) {
	
	if($_POST['product-type'] != 'fiztrade')
		return;
	
	// Validation
	$require_product = $_POST['editing'] == 'edit' ? false : true;
	// $require_sell_premium = isset($_POST['chkSell']) && ! isset( $_POST['chkCall'] );
	// $require_buy_premium = isset($_POST['chkBuy']) && ! isset( $_POST['chkCall'] );
	$require_sell_premium = $_POST['sell_option'] == 'yes' || $_POST['sell_option'] == 'callA';
	$require_buy_premium = $_POST['buy_option'] == 'yes' || $_POST['sell_option'] == 'callA';
	
	if($require_product && empty($_POST['_product_id'])) {
		WC_Admin_Meta_Boxes::add_error('You must select a FizTrade product to proceed.');
		// don't save anything from the metabox if there are any validation errors
		return;
	}
	
	update_post_meta( $post_id, '_sell_option', $_POST['sell_option'] );
	if (isset($_POST['buy_option'])) {
		update_post_meta( $post_id, '_buy_option', $_POST['buy_option'] );
	}
	// update_post_meta( $post_id, '_will_buy', isset( $_POST['chkBuy'] ) ? 'yes' : 'no' );
	// update_post_meta( $post_id, '_will_sell', isset( $_POST['chkSell'] ) ? 'yes' : 'no' );
	// update_post_meta( $post_id, '_call_for_price', isset( $_POST['chkCall'] ) ? 'yes' : 'no');
	// update_post_meta( $post_id, '_call_for_availability', isset( $_POST['chkCallA'] ) ? 'yes' : 'no');
	
	if (isset($_POST['_product_id'])) {
		update_post_meta( $post_id, '_product_id', $_POST['_product_id']);
	}
	
	if (isset($_POST['_product_weight_fiztrade'])) {
		update_post_meta( $post_id, '_product_weight', $_POST['_product_weight_fiztrade']);
	}
	
	if ($require_sell_premium) {
		// update_post_meta( $post_id, '_sell_flat_premium', stripslashes( $_POST['_sell_flat_premium'] ) );
		// update_post_meta( $post_id, '_sell_percent_premium', stripslashes( $_POST['_sell_percent_premium'] ) );
		
		// makes price sorting happy to have a price in the _price field
		// TODO: replace with get_premiums price
		$quote = dm_inv_get_quote($_POST['_product_id']);
		$price = $quote + $quote * (stripslashes( $_POST['_sell_percent_premium'] ) / 100) + stripslashes( $_POST['_sell_flat_premium'] );
		update_post_meta( $post_id, '_price', $price );
	}
	
	// if ($_POST['sell_option'] == 'callPA')
		// update_post_meta( $post_id, '_price', 0 );
	
	if ($_POST['sell_option'] == 'no' && $_POST['buy_option'] == 'no') 
		update_post_meta( $post_id, '_visibility', 'none' );
	else
		update_post_meta( $post_id, '_visibility', 'visible' );
}
add_action('woocommerce_process_product_meta', 'dm_inv_save_fiztrade');

// save dealer product
function dm_inv_save_dealer($post_id) {
	
	if($_POST['product-type'] != 'dealer')
		return;

	// Validation
	// $require_sell_price = isset($_POST['chkSell']) && ! isset( $_POST['call_for_price'] ) && ! isset( $_POST['call_for_price'] );
	// $require_buy_price = isset($_POST['chkBuy']) && ! isset( $_POST['call_for_price'] ) && ! isset( $_POST['call_for_price'] );
	$do_save = true;
	$require_sell_price = $_POST['sell_option'] == 'yes' || $_POST['sell_option'] == 'callA';
	$require_buy_price = $_POST['buy_option'] == 'yes' || $_POST['buy_option'] == 'callA';
	if ($_POST['_price_option'] == 'flat') {
		if($require_sell_price && empty($_POST['_sell_price'])) {
			WC_Admin_Meta_Boxes::add_error('Please enter a price if you want to sell this product.');
			$do_save = false;
		}
		if($require_buy_price && empty($_POST['_buy_price'])) {
			WC_Admin_Meta_Boxes::add_error('Please enter a price if you want to buy this product.');
			$do_save = false;
		}
	} else {
		if($require_sell_price && ($_POST['_sell_price'] == '' && $_POST['_sell_spot_premium'] == '')) {
			WC_Admin_Meta_Boxes::add_error('Please enter a premium if you want to sell this product.');
			$do_save = false;
		}
		if($require_buy_price && ($_POST['_buy_price'] == '' && $_POST['_buy_spot_premium'] == '')) {
			WC_Admin_Meta_Boxes::add_error('Please enter a premium if you want to buy this product.');
			$do_save = false;
		}
		if(empty($_POST['_product_weight_dealer'])) {
			WC_Admin_Meta_Boxes::add_error('Weight is required for Spot and Premium pricing.');
			$do_save = false;
		}
	}
	
	if (!empty($_POST['_product_weight_dealer'])) {
		if (!is_numeric($_POST['_product_weight_dealer'])) {
			$splits = explode('/', $_POST['_product_weight_dealer']);
			
			if (count($splits) != 2 || !is_numeric($splits[0]) || !is_numeric($splits[1])) {
				WC_Admin_Meta_Boxes::add_error('Enter a numeric value (or fraction) for Weight.');
				$do_save = false;
			}
		}
	}
	
	// don't save anything from the metabox if there are any validation errors
	if (!$do_save)
		return;
		
	update_post_meta( $post_id, '_sell_option', $_POST['sell_option'] );
	update_post_meta( $post_id, '_buy_option', $_POST['buy_option'] );
	// update_post_meta( $post_id, '_will_buy', isset( $_POST['chkBuy'] ) ? 'yes' : 'no' );
	// update_post_meta( $post_id, '_will_sell', isset( $_POST['chkSell'] ) ? 'yes' : 'no' );
	// update_post_meta( $post_id, '_call_for_price', isset( $_POST['chkCall'] ) ? 'yes' : 'no');
	// update_post_meta( $post_id, '_call_for_availability', isset( $_POST['chkCallA'] ) ? 'yes' : 'no');
	
	update_post_meta( $post_id, '_price_option', stripslashes( $_POST['_price_option'] ) );
	update_post_meta( $post_id, '_spot_metal', stripslashes( $_POST['_spot_metal'] ) );
	update_post_meta( $post_id, '_calc_method', stripslashes( $_POST['_calc_method'] ) );
	
	update_post_meta( $post_id, '_sell_price', stripslashes( $_POST['_sell_price'] ) );
	update_post_meta( $post_id, '_price', stripslashes( $_POST['_sell_price'] ) ); // makes price sorting happy to have a price in this field
	update_post_meta( $post_id, '_buy_price', stripslashes( $_POST['_buy_price'] ) );
	update_post_meta( $post_id, '_sell_spot_premium', stripslashes( $_POST['_sell_spot_premium'] ) );
	update_post_meta( $post_id, '_buy_spot_premium', stripslashes( $_POST['_buy_spot_premium'] ) );
	
	if (isset($_POST['_product_weight_dealer'])) {
		if (empty($_POST['_product_weight_dealer']))
			update_post_meta( $post_id, '_product_weight', ''); // wouldn't have been necessary if I built it right the first time, but this cleans out old ' oz' fields
		else
			update_post_meta( $post_id, '_product_weight', str_replace(' ', '', $_POST['_product_weight_dealer']) .' '. $_POST['weight_unit']);
	}
	
	// if ($_POST['sell_option'] == 'callPA')
		// update_post_meta( $post_id, '_price', 0 );
	
	if ($_POST['sell_option'] == 'no' && $_POST['buy_option'] == 'no') 
		update_post_meta( $post_id, '_visibility', 'none' );
	else
		update_post_meta( $post_id, '_visibility', 'visible' );
}
add_action('woocommerce_process_product_meta', 'dm_inv_save_dealer');

// we aren't using the short description or product review, so remove them from post administration
function dm_inv_rm_meta_boxes () {
	remove_meta_box( 'commentsdiv', 'product', 'normal' );
	remove_meta_box( 'postexcerpt', 'product', 'normal' );
}
add_action('add_meta_boxes', 'dm_inv_rm_meta_boxes', 50);

?>