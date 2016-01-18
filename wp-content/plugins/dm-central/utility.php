<?php

/**
 * Output a select input box.
 * Modified to support option groups
 *
 * @access public
 * @param array $field
 * @return void
 */
function imaginuity_wp_select( $field ) {
	global $thepostid, $post, $woocommerce;

	$thepostid 				= empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class'] 		= isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value'] 		= isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['id'] ) . '" class="' . esc_attr( $field['class'] ) . '">';

	foreach ( $field['options'] as $key => $value ) {
		
		if (is_array($value)) {		// options is an array of arrays, use the key of the exterior array as optgroup names
			echo '<optgroup label="'. $key .'">';
			foreach ($value as $optVal => $optText) {
				echo '<option value="' . esc_attr( $optVal ) . '" ' . selected( esc_attr( $field['value'] ), esc_attr( $optVal ), false ) . '>' . esc_html( $optText ) . '</option>';
			}
			echo '</optgroup>';
		} else {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
		}
	}

	echo '</select> ';

	if ( ! empty( $field['description'] ) ) {

		if ( isset( $field['desc_tip'] ) ) {
			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';
		} else {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}

	}
	echo '</p>';
}

/**
 * gets the product associated with a given
 * Dillon Gage product code, or false if site doesn't have one published
 *
 * @access public
 * @param array $dg_id
 * @return WC_Product_FizTrade
 */
function get_product_from_dg_id ($dg_id) {
	$query = new WP_Query(array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'product_type' => 'fiztrade',
		'meta_key' => '_product_id',
		'meta_value' => $dg_id
	));
	// there should only be one published FizTrade product with this id
	if ( $query->have_posts() ) {
		$query->next_post();
		return new WC_Product_FizTrade($query->post->ID);
	} else {
		return false;
	}
}

?>