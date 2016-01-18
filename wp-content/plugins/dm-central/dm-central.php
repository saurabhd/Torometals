<?php
/*
 Plugin Name: Digital Metals
 Description: Adds Digital Metals product and order types. Requires WooCommerce 2 - tested through 2.2.10
 Author: Imaginuity & Dillon Gage
 Author URI: http://www.imaginuity.com/
 Version: 2.0.5
 */
define(DIGITALMETALS_VERSION, '2.0.5');
define(DIGITALMETALS_REQ_WP, '3.8');
define(DIGITALMETALS_REQ_WC, '2.1');

define(SERVICE_URL, 'https://www.fiztrade.com');
define(SERVICE_URL_STAGE, 'https://stage.fiztrade.com');
define(INT_TOKEN, '2384-1a3c2c04391039cefc192caff260fa5e');
define(INT_TOKEN_STAGE, 'im789ea99f42323242dd543997156755');

// check for required versions
function dm_check_requirements () {
	global $wp_version;
	$wc_version = get_option('woocommerce_version');
	
	if ( version_compare( $wp_version, DIGITALMETALS_REQ_WP, '<' ) )
		$flag = 'WordPress';
	elseif
		( version_compare( $wc_version, DIGITALMETALS_REQ_WC, '<' ) )
		$flag = 'WooCommerce';
	else
		return;
	$version = 'WordPress' == $flag ? DIGITALMETALS_REQ_WP : DIGITALMETALS_REQ_WC;
	deactivate_plugins( basename( __FILE__ ) );
	wp_die('<p>The <strong>Digital Metals</strong> plugin requires '.$flag.'  version '.$version.' or greater.</p>','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
}
register_activation_hook( __FILE__, 'dm_check_requirements' );

// create products table
function dm_create_products_table () {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "products"; 
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE $table_name (
		Code varchar(30) NOT NULL,
		Name varchar(100),
		MetalType varchar(10),
		Origin varchar(10),
		Weight varchar(15),
		Description varchar(500),
		FamilyID int(11),
		Qty int(11),
		Image varchar(45),
		Purity varchar(30),
		ImagePath varchar(500),
		IsActiveBuy bit(1),
		IsActiveSell bit(1),
		IsIRAEligible bit(1),
		Quote float,
		Obverse varchar(45),
		Inverse varchar(45),
		Mint varchar(45),
		Strike varchar(45),
		Details mediumtext,
		PRIMARY KEY  (Code)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	
	update_option( 'digitalmetals_db_version', DIGITALMETALS_VERSION );
}
register_activation_hook( __FILE__, 'dm_create_products_table' );

function dm_add_15_min ($schedules) {
	$schedules['15min'] = array( 'interval' => 900, 'display' => __('Every 15 minutes') );
	return $schedules;
}
add_filter( 'cron_schedules', 'dm_add_15_min' ); 

function dm_schedule_cron () {
	// check if wp_cron job exists
	$timestamp = wp_next_scheduled( 'dm_update_products' );
	if( $timestamp === false ){
		//Schedule the event for right now, then to repeat every 15min
		wp_schedule_event( time(), '15min', 'dm_update_products' );
		//Schedule the event for right now, then to repeat every day
		wp_schedule_event( time(), 'daily', 'dm_update_market_hours' );
	}
}
register_activation_hook( __FILE__, 'dm_schedule_cron' );

function dm_update_products_func () {
	require_once(dirname(__FILE__) .'/wp-cron/shared.php');
	require_once(dirname(__FILE__) .'/wp-cron/update-products.php');
}
add_action ('dm_update_products', 'dm_update_products_func');

function dm_update_market_hours_func () {
	require_once(dirname(__FILE__) .'/wp-cron/shared.php');
	require_once(dirname(__FILE__) .'/wp-cron/update-hours.php');
}
add_action ('dm_update_market_hours', 'dm_update_market_hours_func');




require_once dirname(dirname(__FILE__)) .'/woocommerce/includes/abstracts/abstract-wc-settings-api.php';
require_once dirname(dirname(__FILE__)) .'/woocommerce/includes/abstracts/abstract-wc-email.php';
require_once dirname(__FILE__) . '/class-wc-email-new-customer.php';
require_once dirname(__FILE__) . '/class-wc-email-rejected-order.php';
require_once dirname(__FILE__) . '/class-wc-email-fiztrade-failure.php';
require_once dirname(__FILE__) . '/widgets/cta.php';
require_once dirname(__FILE__) . '/widgets/product-filter.php';
require_once dirname(__FILE__) . '/widgets/graph-control.php';
require_once dirname(__FILE__) . '/widgets/spots.php';

require_once dirname(__FILE__) . '/dm-inventory.php';
require_once dirname(__FILE__) . '/dm-settings.php';
require_once dirname(__FILE__) . '/dm-emails.php';

// define variables for javascript
function dm_js_vars() { 
	$options = get_option('imag_mall_options_tech');
	
	if ($options['stage_prod'] == 'staging') {
		echo '<script>';
		echo 'var SERVICE_URL = "'. SERVICE_URL_STAGE .'";';
		echo 'var INT_TOKEN = "'. INT_TOKEN_STAGE .'";';
		echo '</script>';
	} else {
		echo '<script>';
		echo 'var SERVICE_URL = "'. SERVICE_URL .'";';
		echo 'var INT_TOKEN = "'. INT_TOKEN .'";';
		echo '</script>';
	}
}
add_action('wp_head', 'dm_js_vars');
add_action('admin_head', 'dm_js_vars');

function dm_scripts() {
	wp_register_style('dm_scripts', plugins_url('/styles.css', __FILE__));
	wp_enqueue_style('dm_scripts');
}
add_action('admin_enqueue_scripts', 'dm_scripts');

