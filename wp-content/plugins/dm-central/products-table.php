<?php
/*
	Customizes WooCommerce Products table for Digital Metals.
*/
function dm_inv_add_columns($existing_columns) {
	if (empty( $existing_columns ) && !is_array( $existing_columns )) {
		$existing_columns = array();
	}
	
	// replace Price column with Ask Price and Bid Price
	if (array_key_exists('price' , $existing_columns )) {
		$new_columns = array();
		$new_columns['ask_price'] = 'Ask Price';
		$new_columns['bid_price'] = 'Bid Price';
		
		$price_position = array_search('price', array_keys($existing_columns));
		
		// slice the old price column out of the array and stitch in the new columns
		$output = array_slice($existing_columns, 0, $price_position, true) +
			$new_columns +
			array_slice($existing_columns, $price_position + 1, NULL, true);
	} else {
		$output = $existing_columns;
	}
	
	return $output;
}
//add_filter( 'manage_edit-product_columns', 'dm_inv_add_columns', 99, 1);
// TODO: move this to Offers

function dm_inv_column_format($column) {
	global $post, $woocommerce, $the_product;

    if ( empty( $the_product ) || $the_product->id != $post->ID ) {
        $the_product = get_product( $post );
	}
	
	switch ($column) {
		case 'ask_price':
			if ($the_product->product_type != 'fiztrade' && $the_product->product_type != 'dealer') {
				echo $the_product->get_price_html();
			} else if ($the_product->get_ask_price_html()) {
				if ($the_product->product_type == 'fiztrade') {
					//echo '<span class="update-ask" data-product-id="'. $the_product->id .'">'.
					echo '<span data-product-id="'. $the_product->id .'">'.
						$the_product->get_ask_price_html() . '</span>';
				} else {
					// don't apply the updater class to dealer items - keeps overhead down
					echo '<span">'.	$the_product->get_ask_price_html() . '</span>';					
				}
			} else {
				echo '<span class="na">&ndash;</span>';
			}
			break;
		case 'bid_price':
			if ($the_product->product_type != 'fiztrade' && $the_product->product_type != 'dealer') {
				echo '<span class="na">&ndash;</span>';
			} else if ($the_product->get_bid_price_html()) {
				if ($the_product->product_type == 'fiztrade') {
					//echo '<span class="update-bid" data-product-id="'. $the_product->id .'">'.
					echo '<span data-product-id="'. $the_product->id .'">'.
						$the_product->get_bid_price_html() . '</span>';
				} else {
					// don't apply the updater class to dealer items - keeps overhead down
					echo '<span">'.	$the_product->get_bid_price_html() . '</span>';					
				}
			} else {
				echo '<span class="na">&ndash;</span>';
			}
			break;
	}
}
add_action( 'manage_product_posts_custom_column', 'dm_inv_column_format',3);
?>