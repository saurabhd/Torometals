<?php


// add fields to WooCommerce -> Settings -> Digital Metals menu
function dm_customers_dm_settings ($settings) {
	$keys = array_keys($settings);
	$customer_pos = array_search('autoforward_title', $keys);
	
	$settings = array_merge(
		array_slice($settings, 0, $customer_pos),
		array(
			'dm_registration_fields_title' => array(
				'name' => __('Required Fields'),
				'type' => 'title'
			),
			'dm_registration_tax_id' => array(
				'name' => __('Tax ID'),
				'desc' => __("Require a tax ID # from customers before ordering."),
				'type' => 'checkbox',
				'id' => 'imag_mall_options_tech[req_tax_id]',
			),
			'dm_registration_dl_num' => array(
				'name' => __('Driver\'s License Number'),
				'desc' => __("Require a driver's license # from customers before ordering."),
				'type' => 'checkbox',
				'id' => 'imag_mall_options_tech[req_dl_num]',
			),
			'dm_registration_end' => array(
				'type' => 'sectionend'
			),
			'dm_unverified_fields_title' => array(
				'name' => __('Unverified Customer Settings'),
				'type' => 'title',
				'desc' => __('<b>Note:</b> Trades made by unverified customers are never auto-forwarded.')
			),
			'dm_unverified_order' => array(
				'name' => __('Allow orders from unverified customers'),
				'type' => 'checkbox',
				'id' => 'imag_mall_options_tech[allow_unverified_order]',
			),
			// unverified offer added below
			'dm_unverified_end' => array(
				'type' => 'sectionend'
			)
		),
		array_slice($settings, $customer_pos)
	);
	
	if (is_plugin_active('dm-offers/dm-offers.php')) {
		$keys = array_keys($settings);
		$offer_pos = array_search('dm_unverified_end', $keys);
		
		$settings = array_merge(
			array_slice($settings, 0, $offer_pos),
			array('dm_unverified_offer' => array(
				'name' => __('Allow offers from unverified customers'),
				'type' => 'checkbox',
				'id' => 'imag_mall_options_tech[allow_unverified_offer]',
			)),
			array_slice($settings, $offer_pos)
		);
	}
	
	
	$users = get_users('role=customer');
	$customers_order = array();
	$customers_offer = array();
	$customers_shipping = array();
	foreach ($users as $user) {
		$customers_order[$user->ID] = dm_check_overrides($user, 'orders');
		$customers_offer[$user->ID] = dm_check_overrides($user, 'offers');
		$customers_shipping[$user->ID] = dm_check_overrides($user, 'shipping');
	}

	$keys = array_keys($settings);
	$auto_fwd_pos = array_search('autoforward_end', $keys);
	
	$settings = array_merge(
		array_slice($settings, 0, $auto_fwd_pos),
		array('dm_settings_auto_order_override' => array(
			'name' => __('Override these settings'),
			'desc' => __("Select a user and click <a class='override-link'>Go</a> to set options for that user that will override these settings.  Users with '*' have overriding settings."),
			'desc_tip' => false,
			'type' => 'select',
			'id' => 'dm_settings_auto_order_override',
			'options' => $customers_order
		)),
		array_slice($settings, $auto_fwd_pos)
	);
	
	if (is_plugin_active('dm-offers/dm-offers.php')) {
		$keys = array_keys($settings);
		$auto_fwd_pos = array_search('autoforward_offers_end', $keys);
		
		$settings = array_merge(
			array_slice($settings, 0, $auto_fwd_pos),
			array('dm_settings_auto_offer_override' => array(
				'name' => __('Override these settings'),
				'desc' => __("Select a user and click <a class='override-link'>Go</a> to set options for that user that will override these settings.  Users with '*' have overriding settings."),
				'desc_tip' => false,
				'type' => 'select',
				'id' => 'dm_settings_auto_offer_override',
				'options' => $customers_offer
			)),
			array_slice($settings, $auto_fwd_pos)
		);
	}

	// shipping
	$keys = array_keys($settings);
	$shipping_pos = array_search('shipping_end', $keys);
	
	$settings = array_merge(
		array_slice($settings, 0, $shipping_pos),
		array('dm_settings_shipping_override' => array(
			'name' => __('Override these settings'),
			'desc' => __("Select a user and click <a class='override-link'>Go</a> to set options for that user that will override these settings.  Users with '*' have overriding settings."),
			'desc_tip' => false,
			'type' => 'select',
			'id' => 'dm_settings_shipping_override',
			'options' => $customers_shipping
		)),
		array_slice($settings, $shipping_pos)
	);
	
	return $settings;
}
add_action('wc_digital_metals_settings', 'dm_customers_dm_settings');

// set a user id or array of userids to have the customer role
function dm_customers_approve($userids) {
	if(is_array($userids)) {
		$count = 0;
		foreach ( $userids as $id ) {
			$result = dm_customers_approve($id);
			if(is_wp_error($result)) {
				return '<div id="message" class="error"><p>' . $result->get_error_message() . '</p></div>';
			}
			$count++;
		}
		$msg = $count > 1 ? 'Users updated.' : 'User updated.';
		return '<div id="message" class="updated"><p>' . $msg . '</p></div>';
	} else {
		// single customer update
		$id = (int) $userids;
		
		if ( ! current_user_can('promote_user', $id) ) {
			return new WP_Error('permission', 'You can&#8217;t edit that user.');
		}

		// If the user doesn't already belong to the blog, bail.
		if ( is_multisite() && !is_user_member_of_blog( $id ) ) {
			return new WP_Error('non-member', 'Cheatin&#8217; uh?');
		}

		$user = new WP_User($id);
		$user->set_role('customer');
		
		return '<div id="message" class="updated"><p>User updated.</p></div>';
	}				
}

// set a user id or array of userids to have the unverified role
function dm_customers_unapprove($userids) {
	if(is_array($userids)) {
		$count = 0;
		foreach ( $userids as $id ) {
			$result = dm_customers_approve($id);
			if(is_wp_error($result)) {
				return '<div id="message" class="error"><p>' . $result->get_error_message() . '</p></div>';
			}
			$count++;
		}
		$msg = $count > 1 ? 'Users updated.' : 'User updated.';
		return '<div id="message" class="updated"><p>' . $msg . '</p></div>';
	} else {
		// single customer update
		$id = (int) $userids;
		
		if ( ! current_user_can('promote_user', $id) ) {
			return new WP_Error('permission', 'You can&#8217;t edit that user.');
		}

		// If the user doesn't already belong to the blog, bail.
		if ( is_multisite() && !is_user_member_of_blog( $id ) ) {
			return new WP_Error('non-member', 'Cheatin&#8217; uh?');
		}

		$user = new WP_User($id);
		$user->set_role('rpr_unverified');
		
		return '<div id="message" class="updated"><p>User updated.</p></div>';
	}				
}

?>