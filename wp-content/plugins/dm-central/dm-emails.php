<?php
// Add notes to New Order emails if they contain FizTrade Items
function dm_admin_new_order_notes ($order, $sent_to_admin, $plain_text) {
	if (!$sent_to_admin)
		return;
		
	// tell the recipient if Dillon Gage items in the order and if they need to be forwarded
	$contains_fiztrade_item = false;
	foreach ($order->get_items() as $item) {
		$product = get_product($item['product_id']);
		if ($product->product_type == 'fiztrade') {	
			$contains_fiztrade_item = true;		
			if (dm_orders_auto_fwd_eligible($order)) {
				$fiztrade_msg = 'This order contains one or more Dillon Gage products.  Orders for these items have been automatically forwarded to FizTrade.';		
			} else {
				$fiztrade_msg = 'This order contains one or more Dillon Gage products.  <b>ACTION REQUIRED:</b> Orders for these items must be <a href="'. get_bloginfo('url') .'/wp-admin/post.php?post='. $order->id .'&action=edit">manually forwarded</a>.';
				break;
			}
		}
	}
	if ($contains_fiztrade_item) {
		if ($plain_text)
			echo "FizTrade Notice\n****************************************************\n\n$fiztrade_message\n";
		else
			echo '<h3>FizTrade Notice</h3><p>'. $fiztrade_msg .'</p>';
	}
}
add_action('woocommerce_email_order_meta', 'dm_admin_new_order_notes', 3, 20);

// creates a list of administrators to whom New Order notifications will be mailed
function dm_admin_new_order(){
	return dm_get_recipients('new-order');
}
add_filter('woocommerce_email_recipient_new_order', 'dm_admin_new_order');

// creates a list of administrators to whom New Offer notifications will be mailed
function dm_admin_new_offer(){
	return dm_get_recipients('new-offer');
}
add_filter('woocommerce_email_recipient_new_offer', 'dm_admin_new_offer');

// creates a list of administrators to whom Contact Us entries will be mailed
function dm_admin_contact_us($value){	
	return dm_get_recipients('contact-us');
}
add_filter('gform_field_value_admin_email_list', 'dm_admin_contact_us');

function dm_add_new_customer_email($emails) {
	//$emails['WC_Email_New_Customer'] = new WC_Email_New_Customer(); // TODO: for Customers plugin
	$emails['WC_Email_Rejected_Order'] = new WC_Email_Rejected_Order();
	$emails['WC_Email_FizTrade_Failure'] = new WC_Email_FizTrade_Failure();
	return $emails;
}
add_filter( 'woocommerce_email_classes', 'dm_add_new_customer_email' );

function dm_add_mailer () {
	global $woocommerce;
	$woocommerce->mailer();
}
add_action('woocommerce_order_status_cancelled', 'dm_add_mailer', 3);
add_action('dg_trade_failure', 'dm_add_mailer', 3);

function dm_get_recipients($notification) {
    $users = get_users();
	
	$recipients = array();
	foreach ($users as $user) {
		if (!($user->has_cap('administrator') || $user->has_cap('shop_manager')))
			continue;
		
		$notifications = get_user_meta($user->id, 'dm_email_notifications', true);
		if ($notifications[$notification])
			$recipients[] = $user->user_email;
	}
	return implode(',', $recipients);
}
?>