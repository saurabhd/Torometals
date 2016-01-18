<?php
// add fields to user
function dm_customers_personal_info ($cust_meta) {
	$new_fieldset = array(
		'personal_info' => array(
			'title' => __( 'Customer Personal Info' ),
			'fields' => array(
				'taxid' => array(
					'label' => __( 'Tax ID' ),
					'description' => ''
				),
				'dl_num' => array(
					'label' => __( 'Driver\'s License #' ),
					'description' => ''
				),
			)
		)
	);
	return $new_fieldset + $cust_meta;
}
add_action('woocommerce_customer_meta_fields', 'dm_customers_personal_info');
?>