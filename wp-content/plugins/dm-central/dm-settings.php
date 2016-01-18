<?php

// defaults for various settings
global $dm_settings_defaults;
$dm_settings_defaults = array(
	'stage_prod' => 'staging',
	'req_tax_id' => false,
	'req_dl_num' => false,
	'allow_unverified_order' => false,
	'allow_unverified_offer' => false,
	'auto_order' => 'true',
	'auto_order_lt' => false,
	'auto_order_lt_amount' => 0,
	'auto_order_conj' => 'or',
	'auto_order_customers' => false,
	'auto_order_customer_list' => array(),
	'auto_offer' => 'true',
	'auto_offer_lt' => false,
	'auto_offer_lt_amount' => 0,
	'auto_offer_conj' => 'or',
	'auto_offer_customers' => false,
	'auto_offer_customer_list' => array(),
	'ship_to_consumer' => 'drop_ship',
	'allow_store_pickup' => true,
	'allow_shipping' => true,
	'allow_storage' => true,
	'allow_hold' => true
);
	
global $dm_email_defaults;
$dm_email_defaults = array(
	'new-order' => true,
	'new-offer' => true,
	'new-customer' => true,
	'contact-us' => true,
	'new-product' => true,
);

// set defaults for DM settings
function dm_info_set_defaults () {
	global $dm_settings_defaults;
	
	$options = get_option('imag_mall_options_tech');
	if ($options === false)
		$options = array();
	foreach ($dm_settings_defaults as $key => $val) {
		if (!array_key_exists($key, $options)) {
			$options[$key] = $val;
		}
	}
	update_option('imag_mall_options_tech', $options);
}
register_activation_hook( dirname(__FILE__) .'/dm-central.php', 'dm_info_set_defaults' );
//add_action('admin_init', 'dm_info_set_defaults');



function dm_settings_script($hook) {
	if ($_GET['page'] == 'wc-settings' && 
		($_GET['tab'] == 'digital_metals' || $_GET['tab'] == 'digital_metals_email_notifications')) {
		wp_register_style('dm_central', plugins_url() .'/dm-central/styles.css');
		wp_enqueue_style('dm_central');
		
		wp_register_style('dm_settings', plugins_url('/settings-styles.css', __FILE__));
		wp_enqueue_style('dm_settings');
		
		wp_register_script('dm_settings', plugins_url('/settings.js', __FILE__), array('jquery'));
		wp_enqueue_script('dm_settings',null, null, null, true);
	}

}
add_action('admin_enqueue_scripts', 'dm_settings_script');

// add tab to WooCommerce Settings page
function dm_settings_tab ($settings_tabs) {
	$settings_tabs['digital_metals'] = __( 'Digital Metals', 'woocommerce' );
	return $settings_tabs;
}
add_filter('woocommerce_settings_tabs_array', 'dm_settings_tab', 50);


function dm_settings_main() {
	$settings = array(
		'service_settings_title' => array(
			'name' => __('Service Settings', 'woocommerce'),
			'type' => 'title'
		),
		'dm_settings_main_trader_id' => array(
			'name' => __('Dealer ID', 'woocommerce'),
			'type' => 'text',
			'class' => 'widefat',
			'id' => 'imag_mall_options_tech[trader_id]'
		),
		'dm_settings_dealer_token_staging' => array(
			'name' => __('Dealer Token - Staging', 'woocommerce'),
			'type' => 'text',
			'class' => 'widefat',
			'id' => 'imag_mall_options_tech[dealer_token_staging]',
		),
		'dm_settings_dealer_token' => array(
			'name' => __('Dealer Token - Production', 'woocommerce'),
			'type' => 'text',
			'class' => 'widefat',
			'id' => 'imag_mall_options_tech[dealer_token]',
		),
		'dm_settings_stage_prod' => array(
			'name' => __('Staging or Production?', 'woocommerce'),
			'type' => 'radio',
			'id' => 'imag_mall_options_tech[stage_prod]',
			'options' => array( 'staging' => 'Use staging services', 'production' => 'Use production services' )
		),
		'service_settings_end' => array(
			'type' => 'sectionend'
		),
		'autoforward_title' => array(
			'name' => __('FizTrade Order Submission Settings', 'woocommerce'),
			'type' => 'title'
		),
		'dm_settings_auto_order' => array(
			'name' => __('Automatically submit orders', 'woocommerce'),
			'desc' => __('If yes, orders made by customers that fit the criteria set below will be automatically forwarded to FizTrade.', 'woocommerce'),
			'desc_tip' => true,
			'type' => 'radio',
			'id' => 'imag_mall_options_tech[auto_order]',
			'options' => array( 'true' => 'Yes', 'false' => 'No' )
		),
		'dm_settings_auto_order_lt' => array(
			'name' => __('Less than', 'woocommerce'),
			'type' => 'checkbox',
			'id' => 'imag_mall_options_tech[auto_order_lt]'
		),
		'dm_settings_auto_order_lt_amount' => array(
			//'name' => __('Less than', 'woocommerce'),
			'type' => 'number',
			'id' => 'imag_mall_options_tech[auto_order_lt_amount]',
			'class' => 'currency'
		),
		'autoforward_end' => array(
			'type' => 'sectionend'
		),
		'shipping_title' => array(
			'name' => __('Shipping Options', 'woocommerce'),
			'type' => 'title'
		),
		'dm_settings_ship_to_consumer' => array(
			'name' => __('Shipping to consumers', 'woocommerce'),
			'type' => 'radio',
			'id' => 'imag_mall_options_tech[ship_to_consumer]',
			'options' => array( 
				'drop_ship' => 'Dropship - Dillon Gage will drop ship to customer',
				'hold' => 'Dropship Hold - Dillon Gage will place order on drop ship hold awaiting dealer release in FizTrade',
				'ship_to_me' => 'Ship to Dealer - Dillon Gage will ship directly to the dealer and will accumulate orders under 15 oz for Platinum, Palladium and Gold. Dillon Gage will accumlate orders under 300 oz of Silver.'
			)
		),
		'shipping_end' => array(
			'type' => 'sectionend'
		),
	);
	return apply_filters('wc_digital_metals_settings', $settings);
}

// put settings on new tab
function dm_settings_main_do_page() {
	woocommerce_admin_fields(dm_settings_main());
}
add_action ('woocommerce_settings_tabs_digital_metals', 'dm_settings_main_do_page');

// save settings
function dm_settings_update_settings() {
    woocommerce_update_options(dm_settings_main());
}
add_action( 'woocommerce_update_options_digital_metals', 'dm_settings_update_settings' );

// add email notification settings to user page
function dm_settings_email_notifications($user) {
	if (!($user->has_cap('administrator') || $user->has_cap('shop_manager')))
		return;
	
	global $dm_email_defaults;
	
	$settings = get_user_meta($user->id, 'dm_email_notifications', true);
	if (empty($settings))
		$settings = array();
	
	foreach ($dm_email_defaults as $key => $value) {
		if (!array_key_exists($key, $settings))
			$settings[$key] = $value;
	}
	?>
	<h3>Digital Metals Emails to Me</h3>

	<table class="form-table">
		<tr>
			<td>
				<input type="checkbox" id="dm_email_notifications[new-order]" name="dm_email_notifications[new-order]" <?php checked($settings['new-order'], true); ?>/>
				<label for="dm_email_notifications[new-order]"> Notify me of new orders</label>
			</td>
		</tr>
		<?php if (function_exists('dm_offers_init')) : ?>
		<tr>
			<td>
				<input type="checkbox" id="dm_email_notifications[new-offer]" name="dm_email_notifications[new-offer]" <?php checked($settings['new-offer'], true); ?>/>
				<label for="dm_email_notifications[new-offer]"> Notify me of new offers</label>
			</td>
		</tr>
		<?php endif; ?>
		<?php if (function_exists('dm_customers_isapproved')) : ?>
		<tr>
			<td>
				<input type="checkbox" id="dm_email_notifications[new-customer]" name="dm_email_notifications[new-customer]" <?php checked($settings['new-customer'], true); ?>/>
				<label for="dm_email_notifications[new-customer]"> Notify me when a new user needs to be verified</label>
			</td>
		</tr>
		<?php endif; ?>
		<!--<tr>
			<td>
				<input type="checkbox" id="dm_email_notifications[contact-us]" name="dm_email_notifications[contact-us]" <?php checked($settings['contact-us'], true); ?>/>
				<label for="dm_email_notifications[contact-us]"> Notify me when a user submits a Contact Us form</label>
			</td>
		</tr>-->
		<tr>
			<td>
				<input type="checkbox" id="dm_email_notifications[new-product]" name="dm_email_notifications[new-product]" <?php checked($settings['new-product'], true); ?>/>
				<label for="dm_email_notifications[new-product]"> Notify me when products have been added or removed from FizTrade</label>
			</td>
		</tr>
	</table>
	<!--
	<br/><br/>
	<h3 class="left">Emails to Customers</h3><a class="left" href="<?php echo admin_url('admin.php?page=woocommerce_settings&tab=email'); ?>">WooCommerce Email Settings</a>
	<div class="clear">
	<p class="explanation">
		<b>Rejected Order</b> - Edit this box to change the body text of the email sent to customers when their order status is set to 'cancelled'.
	</p>
	<?php $cust_emails = get_option('imag_mall_customer_emails'); ?>
	<?php wp_editor($cust_emails, 'rejected-order', array(
		'textarea_name' => 'imag_mall_skip_email_notifications[rejected-order]',
		'textarea_rows' => 6
	)); ?>-->
<?php
}
add_action('show_user_profile', 'dm_settings_email_notifications');
add_action('edit_user_profile', 'dm_settings_email_notifications');

function dm_mall_email_notifications_update($user_id) {
	// set rejected order text
	//update_option('imag_mall_customer_emails', $input['rejected-order']);
	global $dm_email_defaults;
	
	$settings = get_user_meta($user_id, 'dm_email_notifications', true);
	if (empty($settings))
		$settings = $dm_email_defaults;
	
	$posted = $_POST['dm_email_notifications'];
	
	foreach (array_keys($settings) as $key) {
		$settings[$key] = filter_var($posted[$key], FILTER_VALIDATE_BOOLEAN);
	}
	
	update_user_meta($user_id, 'dm_email_notifications', $settings);
}
add_action('personal_options_update', 'dm_mall_email_notifications_update');
add_action('edit_user_profile_update', 'dm_mall_email_notifications_update');

function dm_check_overrides ($user, $setting) {
	global $wpdb;
	
	if (is_multisite())
		$prefix = $wpdb->prefix;
	else
		$prefix = '';
	
	switch ($setting) {
		case 'orders':
			$setting = get_user_meta( $user->id, $prefix .'auto_order', true);
			break;
		case 'offers':
			$setting = get_user_meta( $user->id, $prefix .'auto_offer', true);
			break;
		case 'shipping':
			$setting = get_user_meta( $user->id, $prefix .'ship_to_consumer', true);
			break;
		default:
			wp_die('Error in dm_check_overrides');
	}
	
	//wp_die(print_r($user,true));
	//wp_die('id:'.$user_id.' name:'.$cust_name.' order :'.$order_setting . ' offer :'.$offer_setting);
	if (empty($setting) || $setting == 'inherit') 
		return $user->display_name;
	else
		return $user->display_name .' *';
}
