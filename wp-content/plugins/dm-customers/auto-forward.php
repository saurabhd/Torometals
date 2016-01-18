<?php

// add options to user edit page to let admin control when their orders are auto-forwarded
function dm_customers_auto_forward_fields ($user) {
	global $wpdb;
	if (!current_user_can('promote_users'))
		return;
		
	$temp = array_map( function ($a) { return $a[0]; }, get_user_meta($user->id));
	if (is_multisite()) {
		// filter out auto-forward settings from other sites
		$user_meta = array();
		foreach ($temp as $key => $val) {
			if (strpos($key, $wpdb->prefix) !== false) {
				$new_key = str_replace($wpdb->prefix, '',$key);
				$user_meta[$new_key] = $val;
			}
		}
	} else {
		$user_meta = $temp;
	}
	?>

	<table class="form-table">
		<tr valign="top" id="overrides">
			<th colspan="2"><h3 id="auto-forward-order" >FizTrade Order Submission Settings</h3></th>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="auto_order">Automatically submit orders</label>
				<img class="help-tip" title='If yes, orders made by this customer that fit the criteria set below will be automatically forwarded to FizTrade.' 
					src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
			</th>
			<td>
				<input type="radio" name="auto_order" value="true" <?php checked($user_meta['auto_order'], 'true'); ?> />Yes &nbsp;&nbsp;
				<input type="radio" name="auto_order" value="false" <?php checked($user_meta['auto_order'], 'false'); ?> />No &nbsp;&nbsp;
				<input type="radio" name="auto_order" value="inherit" <?php if (!isset($user_meta['auto_order']) || $user_meta['auto_order'] == 'inherit') echo 'checked="checked"'; ?> />Use site settings  &nbsp;&nbsp;<a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=digital_metals">Site Settings</a>
			</td>
		</tr>	
		<tr valign="top" class="box first last gray-if-manual-order">
			<th scope="row">
				<input type="checkbox" name="auto_order_lt" value="true" <?php checked($user_meta['auto_order_lt'], 'true'); ?> />
				&nbsp;&nbsp;<label for="auto_order_lt">Less than</label>
			</th>
			<td>
				$<input type="text" size="20" name="auto_order_lt_amount" value="<?php echo $user_meta['auto_order_lt_amount']; ?>" />
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<?php if (is_plugin_active('dm-offers/dm-offers.php')) : ?>
			<tr valign="top">
				<th colspan="2"><h3 id="auto-forward-offer" >FizTrade Offer Submission Settings</h3></th>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="auto_offer">Automatically submit offers</label>
					<img class="help-tip" title='If yes, offers made by this customer that fit the criteria set below will be automatically forwarded to FizTrade.' 
						src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
				</th>
				<td>
					<input type="radio" name="auto_offer" value="true" <?php checked($user_meta['auto_offer'], 'true'); ?> />Yes &nbsp;&nbsp;
					<input type="radio" name="auto_offer" value="false" <?php checked($user_meta['auto_offer'], 'false'); ?> />No &nbsp;&nbsp;
					<input type="radio" name="auto_offer" value="inherit" <?php if (!isset($user_meta['auto_offer']) || $user_meta['auto_offer'] == 'inherit') echo 'checked="checked"'; ?> />Use site settings  &nbsp;&nbsp;<a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=digital_metals">Site Settings</a>
				</td>
			</tr>	
			<tr valign="top" class="box first last gray-if-manual-offer">
				<th scope="row">
					<input type="checkbox" name="auto_offer_lt" value="true" <?php checked($user_meta['auto_offer_lt'], 'true'); ?> />
					&nbsp;&nbsp;<label for="auto_offer_lt">Less than</label>
				</th>
				<td>
					$<input type="text" size="20" name="auto_offer_lt_amount" value="<?php echo $user_meta['auto_offer_lt_amount']; ?>" />
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
		<?php endif; //end if is_plugin_active('dm-offers') ?>
			<tr valign="top">
				<th colspan="2"><h3>Shipping Options</h3></th>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="ship_to_consumer">Shipping to this customer</label>
				</th>
				<td>
					<input type="radio" name="ship_to_consumer" value="drop_ship" <?php checked($user_meta['ship_to_consumer'], 'drop_ship'); ?> /><label for="drop_ship">Dropship - Dillon Gage will drop ship to customer</label><br/>
					<input type="radio" name="ship_to_consumer" value="hold" <?php checked($user_meta['ship_to_consumer'], 'hold'); ?> /><label for="ship_to_me">Dropship Hold - Dillon Gage will place order on drop ship hold awaiting dealer release in FizTrade</label><br/>
					<input type="radio" name="ship_to_consumer" value="ship_to_me" <?php checked($user_meta['ship_to_consumer'], 'ship_to_me'); ?> /><label for="ship_to_me">Ship to Dealer - Dillon Gage will ship directly to the dealer and will accumulate orders under 15 oz for Platinum, Palladium and Gold. Dillon Gage will accumlate orders under 300 oz of Silver.</label><br/>
					<input type="radio" name="ship_to_consumer" value="inherit" <?php if (!isset($user_meta['ship_to_consumer']) || $user_meta['ship_to_consumer'] == 'inherit') echo 'checked="checked"'; ?> /><label for="inherit">Use site settings <a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=digital_metals">Site Settings</a></label>
				</td>
			</tr>
	</table>

	<?php
}
add_action( 'show_user_profile', 'dm_customers_auto_forward_fields', 12 );
add_action( 'edit_user_profile', 'dm_customers_auto_forward_fields', 12 );


function dm_customers_auto_forward_save( $user_id ) {
	global $wpdb;
	
	if ( !current_user_can( 'promote_users') ) 
		return false;
		
	$user = new WP_User($user_id);
	$user->set_role($_POST['role']);
	
	if (is_multisite())
		$prefix = $wpdb->prefix;
	else
		$prefix = '';
		
	update_user_meta( $user_id, $prefix .'auto_order', $_POST['auto_order'] );
	update_user_meta( $user_id, $prefix .'auto_order_lt', $_POST['auto_order_lt'] );
	update_user_meta( $user_id, $prefix .'auto_order_lt_amount', $_POST['auto_order_lt_amount'] );
	update_user_meta( $user_id, $prefix .'auto_offer', $_POST['auto_offer'] );
	update_user_meta( $user_id, $prefix .'auto_offer_lt', $_POST['auto_offer_lt'] );
	update_user_meta( $user_id, $prefix .'auto_offer_lt_amount', $_POST['auto_offer_lt_amount'] );
	update_user_meta( $user_id, $prefix .'ship_to_consumer', $_POST['ship_to_consumer'] );
}
add_action( 'personal_options_update', 'dm_customers_auto_forward_save' );
add_action( 'edit_user_profile_update', 'dm_customers_auto_forward_save' );