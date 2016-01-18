<?php 

// add fields to checkout page, if required
function dm_customers_checkout_fields ($checkout) {
	$tech_options = get_option('imag_mall_options_tech');
	$req_tax_id = isset($tech_options['req_tax_id']) ? $tech_options['req_tax_id'] : false;
	$req_dl_num = isset($tech_options['req_dl_num']) ? $tech_options['req_dl_num'] : false;
	
	if ($req_tax_id) {
		$taxid = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'taxid', true) : '';
		woocommerce_form_field('taxid', array(
			'type' => 'text',
			'label' => 'Tax ID',
			'id' => 'taxid',
			'required' => true
		),
		$taxid);
	}
	
	if ($req_dl_num) {
		$dl_num = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'dl_num', true) : '';
		woocommerce_form_field('dl_num', array(
			'type' => 'text',
			'label' => 'Driver\'s License #',
			'id' => 'dl_num',
			'required' => true
		),
		$dl_num);
	}
}
add_action('woocommerce_checkout_after_customer_details', 'dm_customers_checkout_fields');

function dm_customers_checkout_field_process () {
	$tech_options = get_option('imag_mall_options_tech');
	$req_tax_id = isset($tech_options['req_tax_id']) ? $tech_options['req_tax_id'] : false;
	$req_dl_num = isset($tech_options['req_dl_num']) ? $tech_options['req_dl_num'] : false;
	$valid = true;
	
	if ($req_tax_id && !$_POST['taxid'])
		wc_add_notice('Please enter your Tax ID.', 'error');
	
	if ($req_dl_num && !$_POST['dl_num'])
		wc_add_notice('Please enter your Driver\'s License #.', 'error');
}
add_action('woocommerce_checkout_process', 'dm_customers_checkout_field_process');

function dm_customers_checkout_field_update ($order_id) {
	if (is_user_logged_in()) {
		$user_id = get_current_user_id();
		if (!empty($_POST['taxid']))
			update_user_meta($user_id, 'taxid', $_POST['taxid']);
		if (!empty($_POST['dl_num']))
			update_user_meta($user_id, 'dl_num', $_POST['dl_num']);
	}
}
add_action('woocommerce_checkout_update_order_meta', 'dm_customers_checkout_field_update');

// make username invalid characters message more clear
function dm_cust_invalid_username_msg ($val_result) {
	$errors = $val_result['errors'];
	
	$new_errors = new WP_Error();
	foreach ($errors->errors as $code => $messages) {
		foreach ($messages as $message) {
			if ($code != 'user_name')
				$new_errors->add($code, $message);
			else
				$new_errors->add($code, str_replace('are allowed.', 'are allowed in username.', $message));
		}
	}
	
	$output = $val_result;
	$output['errors'] = $new_errors;
	
	return $output;
}
add_filter( 'wpmu_validate_user_signup', 'dm_cust_invalid_username_msg' );

?>