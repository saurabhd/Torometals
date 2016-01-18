<?php
/*
 Plugin Name: Digital Metals Customers
 Description: Adds a customer administration menu that allows an administrator to set what customers may trade on the site.
 Author: Imaginuity & Dillon Gage
 Author URI: http://www.imaginuity.com/
 Version: 2.0.0
 */

// check for required version
function dm_customers_check_requirements () {
	$min_dm_version = '2.0.2';
	$dm_version = get_option('digitalmetals_db_version');
	
	if ( !function_exists('dm_create_products_table') ) {		
		deactivate_plugins( basename( __FILE__ ) );
		wp_die('<p>The <strong>Digital Metals Customers</strong> plugin requires the <strong>Digital Metals</strong> to be active.</p>','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
	}
	if ( version_compare( $dm_version, $min_dm_version, '<' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die('<p>Please update the <strong>Digital Metals</strong> plugin to at least version '.$min_dm_version.' before activating this plugin.</p>','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
	}
	
	// add unverified role
	$cust = get_role('customer');
	$cap = $cust->capabilities;
	remove_role('rpr_unverified');
	add_role('rpr_unverified', 'Unverified', $cap);
}
register_activation_hook( __FILE__, 'dm_customers_check_requirements' );

require_once dirname(__FILE__) . '/settings.php';
require_once dirname(__FILE__) . '/register.php';
require_once dirname(__FILE__) . '/my-account.php';
require_once dirname(__FILE__) . '/user-view.php';
require_once dirname(__FILE__) . '/auto-forward.php';

function dm_customers_register_script($hook) {	
	if ($hook == 'user-edit.php' || $hook == 'user-new.php' || $_GET['tab'] == 'digital_metals') {
		wp_register_style('dm_customers', plugins_url('style.css', __FILE__));
		wp_enqueue_style('dm_customers');
		
		wp_register_script('dm_customers', plugins_url('script.js', __FILE__), array('jquery'));
		wp_localize_script('dm_customers', 'WPURLS', array('admin' => get_admin_url()));
		wp_enqueue_script('dm_customers',null, null, null, true);
	}
	
}
add_action( 'admin_enqueue_scripts', 'dm_customers_register_script' );

function dm_customers_add_to_cart ($cart) {	
	global $product;
	
	if (!is_user_logged_in()) {
		echo apply_filters('dm_filter_login_to_add', $product->ID);
		return false;
	}
	
	$options = get_option('imag_mall_options_tech');
	$allow_unverified_order = $options['allow_unverified_order'];
	if (is_string($allow_unverified_order))
		$allow_unverified_order = $allow_unverified_order == 'yes' ? true : false;
	$allow_unverified_offer = $options['allow_unverified_offer'];
	if (is_string($allow_unverified_offer))
		$allow_unverified_offer = $allow_unverified_offer == 'yes' ? true : false;
		
	if (!user_can(get_current_user_id(), 'customer') && 
		(($cart == 'bid' && !$allow_unverified_order) || ($cart == 'offer' && !$allow_unverified_offer))) {
		echo apply_filters('dm_filter_get_verified_to_add', get_current_user_id());
		return false;
	}
	return true;
}

// displayed instead of "Add to Cart" when the user isn't logged in
function dm_customers_login_to_add($productID) {
	return '<p>Please <a href="'. wp_login_url(get_permalink()) .'">log in</a> to add this item to your cart.</p>';
}
add_filter('dm_customers_login_to_add', 'dm_inv_login_to_add', 50, 1);

// displayed instead of "Add to Cart" when the user isn't verified
function dm_inv_get_verified_to_add($userID) {
	return '<p>We\'re sorry, you can\'t add this item to your cart until your account has been verified.</p>';
}
add_filter('dm_filter_get_verified_to_add', 'dm_inv_get_verified_to_add', 5, 1);

// check if a customer is approved
function dm_customers_isapproved($user_id) {
	$user = get_userdata($user_id);
	
	if(in_array('customer', $user->roles)) {
		return true;
	} else {
		return false;
	}
}

// new customer email
function dm_customers_add_email($emails) {
	$emails['WC_Email_New_Customer'] = new WC_Email_New_Customer();
	return $emails;
}
add_filter( 'woocommerce_email_classes', 'dm_customers_add_email' );

// creates a list of administrators to whom New customer notifications will be mailed
function dm_customers_admin_new_customer(){
	return dm_get_recipients('new-customer');
}
add_filter('woocommerce_email_recipient_new_customer', 'dm_customers_admin_new_customer');

