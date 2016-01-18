<?php
// adds Tax ID and Driver's License # fields to My Account page
function dm_cust_extra_user_data () {
	global $woocommerce;
	global $current_user;
	$success_msg = '';
	$fn_err = '';
	$dl_err = '';
	$tax_err = '';
				
	// see what extra fields are required
	$tech_options = get_option('imag_mall_options_tech');
	$req_tax_id = isset($tech_options['req_tax_id']) ? $tech_options['req_tax_id'] : false;
	if (is_string($req_tax_id))
		$req_tax_id = $req_tax_id == 'yes' ? true : false;
	$req_dl_num = isset($tech_options['req_dl_num']) ? $tech_options['req_dl_num'] : false;
	if (is_string($req_dl_num))
		$req_dl_num = $req_dl_num == 'yes' ? true : false;
	
	if (isset($_POST['update'])) {
		$taxid = $_POST['taxid'];
		$dl_num = $_POST['dl_num'];			
		
		// update user info
		if ($req_tax_id) {
			if ( !empty( $_POST['taxid'] ) )
				update_user_meta($current_user->ID, 'taxid', $_POST['taxid']);
		}
		
		if ($req_dl_num) {
			if ( !empty( $_POST['dl_num'] ) )
				update_user_meta($current_user->ID, 'dl_num', $_POST['dl_num']);
		}
		
		// if ($fn_err == '' && $dl_err == '' && $tax_err == '')
			// wc_add_notice('Customer details updated.');
			
	} else {
		$full_name = $current_user->display_name;
		$taxid = get_user_meta($current_user->ID, 'taxid', true);
		$dl_num = get_user_meta($current_user->ID, 'dl_num', true);		
	}
	
	if ($req_tax_id || $req_dl_num) {
	?>
	<br/><br/>
	<form method="post">
		<div class="basic_info">
			<?php if ($req_tax_id) : ?>
			<p class="form-row form-row-first" id="taxid_field">			
				<label for="taxid">Tax ID <span class="required">*</span></label>
				<input type="text" id="taxid" name="taxid" value="<?php echo $taxid; ?>" />
			</p>
			<?php endif; ?>
			
			<?php if ($req_dl_num) : ?>
			<p class="form-row form-row-last" id="dl_num_field">			
				<label for="dl_num">Driver's License #  <span class="required">*</span></label>
				<input type="text" id="dl_num" name="dl_num" value="<?php echo $dl_num; ?>" />
			</p>
			<?php endif; ?>
			<div class="clear"></div>
		</div>
		<input type="submit" class="button" name="update" value="Update">
		<?php echo $success_msg; echo $fn_err; echo $dl_err; echo $tax_err; ?>
	</form>
	<br/><br/>
	<?php
	}
}
add_action('woocommerce_before_my_account', 'dm_cust_extra_user_data');
?>