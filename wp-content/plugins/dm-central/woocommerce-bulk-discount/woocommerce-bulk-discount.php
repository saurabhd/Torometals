<?php
/*
Plugin modified and incorporated into DM Inventory by Imaginuity[JOB]
Using the frontend portion of this; see volume-breaks.php for the backend.

Plugin Name: WooCommerce Bulk Discount
Plugin URI: http://wordpress.org/plugins/woocommerce-bulk-discount/
Description: Apply fine-grained bulk discounts to items in the shopping cart.
Author: Rene Puchinger
Version: 2.2
Author URI: http://www.renepuchinger.com
License: GPL3

    Copyright (C) 2013  Rene Puchinger

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'Woo_Bulk_Discount_Plugin_t4m' ) ) {

	class Woo_Bulk_Discount_Plugin_t4m {

		var $discount_coeffs;
		var $bulk_discount_calculated = false;

		public function __construct() {

			load_plugin_textdomain( 'wc_bulk_discount', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

			// $this->current_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'general';

			// $this->settings_tabs = array(
				// 'bulk_discount' => __( 'Bulk Discount', 'wc_bulk_discount' )
			// );

			add_action( 'wp_head', array( $this, 'action_enqueue_dependencies' ) );

			// add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );


			// Add the settings fields to each tab.
			// add_action( 'woocommerce_bulk_discount_settings', array( $this, 'add_settings_fields' ), 10 );

			add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded' ) );

		}

		/**
         * Main processing hooks
		 */
		public function woocommerce_loaded() {

			if ( get_option( 'woocommerce_t4m_enable_bulk_discounts', 'yes' ) == 'yes' ) {

				add_action( 'woocommerce_before_calculate_totals', array( $this, 'action_before_calculate' ), 10, 1 );
				add_action( 'woocommerce_calculate_totals', array( $this, 'action_after_calculate' ), 10, 1 );
				add_action( 'woocommerce_before_cart_table', array( $this, 'before_cart_table' ) );
				//add_action( 'woocommerce_single_product_summary', array( $this, 'single_product_summary' ), 45 );
				add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'filter_subtotal_price' ), 10, 2 );
				add_filter( 'woocommerce_offer_cart_item_subtotal', array( $this, 'filter_subtotal_price' ), 10, 2 );
				add_filter( 'woocommerce_checkout_item_subtotal', array( $this, 'filter_subtotal_price' ), 10, 2 );
				add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'filter_subtotal_order_price' ), 10, 3 );
				//add_filter( 'woocommerce_product_write_panel_tabs', array( $this, 'action_product_write_panel_tabs' ) );
				//add_filter( 'woocommerce_product_write_panels', array( $this, 'action_product_write_panels' ) );
				//add_action( 'woocommerce_process_product_meta', array( $this, 'action_process_meta' ) );
				// if (function_exists('is_offer_cart') && is_offer_cart()) {
					// // skip
				// } else {
					// //add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'filter_cart_product_subtotal' ), 10, 3 );
				// }
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_update_meta' ) );

				if ( version_compare( WOOCOMMERCE_VERSION, "2.1.0" ) >= 0 ) {
					add_filter( 'woocommerce_cart_item_price', array( $this, 'filter_item_price' ), 10, 2 );
					add_filter( 'woocommerce_update_cart_validation', array( $this, 'filter_before_calculate' ), 10, 1 );
				} else {
					add_filter( 'woocommerce_cart_item_price_html', array( $this, 'filter_item_price' ), 10, 2 );
					add_filter( 'woocommerce_offer_cart_item_price_html', array( $this, 'filter_item_price' ), 10, 2 );
				}

			}

		}


		/**
		 * Filter product price so that the discount is visible.
		 *
		 * @param $price
		 * @param $values
		 * @return string
		 */
		public function filter_item_price( $price, $values ) {

			if ( !$values || @!$values['data'] ) {
				return $price;
			}
			if ( $this->coupon_check() ) {
				return $price;
			}
			$_product = $values['data'];
			if ($product->product_type == 'dealer') { // possibly skip some calculations
				$breaks = get_post_meta($_product->id, '_volume_breaks', true);
				if ( count($breaks) < 2 ) 
					return $price;
			}
			if ( empty( $this->discount_coeffs ) || !isset( $this->discount_coeffs[$this->get_actual_id( $_product )] )
				|| !isset( $this->discount_coeffs[$this->get_actual_id( $_product )]['orig_price'] ) || !isset( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] )
			) {
				$this->gather_discount_coeffs();
			}
			$coeff = $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'];
			if ( $coeff == 0 ) {
				return $price; // no price modification
			}
			// NOTE: modifying this to use a discount value instead of coefficient
			if (function_exists('is_offer_cart')) {
				if (is_offer_cart())
					$discounted_price = $_product->get_price('sell');
			}
			if (empty($discounted_price))
				$discounted_price = $_product->get_price();
			
			$discprice = woocommerce_price( $discounted_price );
			$oldprice = woocommerce_price( $this->discount_coeffs[$this->get_actual_id( $_product )]['orig_price'] );
			$old_css = esc_attr( get_option( 'woocommerce_t4m_css_old_price', 'color: #777; text-decoration: line-through; margin-right: 4px;' ) );
			$new_css = esc_attr( get_option( 'woocommerce_t4m_css_new_price', 'color: #4AB915; font-weight: bold;' ) );
			return "<span class='discount-info' title='" . sprintf( __( '%s%% bulk discount applied!', 'wc_bulk_discount' ), woocommerce_price($coeff) ) . "'>" .
			"<span class='old-price' style='$old_css'>$oldprice</span>" .
			"<span class='new-price' style='$new_css'>$discprice</span></span>";

		}

		/**
		 * Filter product price so that the discount is visible.
		 *
		 * @param $price
		 * @param $values
		 * @return string
		 */
		public function filter_subtotal_price( $price, $values ) {

			if ( !$values || !$values['data'] ) {
				return $price;
			}
			if ( $this->coupon_check() ) {
				return $price;
			}
			$_product = $values['data'];

			if ( empty( $this->discount_coeffs ) || !isset( $this->discount_coeffs[$this->get_actual_id( $_product )] )
				|| !isset( $this->discount_coeffs[$this->get_actual_id( $_product )]['orig_price'] ) || !isset( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] )
			) {
				$this->gather_discount_coeffs();
			}
			$coeff = $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'];
			if ( $coeff == 0 ) {
				return $price; // no price modification
			}
			$new_css = esc_attr( get_option( 'woocommerce_t4m_css_new_price', 'color: #4AB915; font-weight: bold;' ) );
			if (function_exists('is_offer_cart') && is_offer_cart() ||
				function_exists('is_offer_checkout') && is_offer_checkout())
				$bulk_info = sprintf( __( 'Incl. %s volume bonus', 'wc_bulk_discount' ), woocommerce_price($coeff) );
			else
				$bulk_info = sprintf( __( 'Incl. %s volume discount', 'wc_bulk_discount' ), woocommerce_price($coeff) );

			return "<span class='discount-info' title='$bulk_info'>" .
			"<span>$price</span>" .
			"<span class='new-price' style='$new_css'> ($bulk_info)</span></span>";

		}

		/**
		 * Gather discount information to the array $this->discount_coefs
		 */
		protected function gather_discount_coeffs() {

			global $offer_cart;

			if (function_exists('is_offer_cart')) {
				if (is_offer_cart())
					$cart = $offer_cart;
			}
			if (empty($cart))
				$cart = WC()->cart;
			
			
			$this->discount_coeffs = array();

			if ( sizeof( $cart->cart_contents ) > 0 ) {
				foreach ( $cart->cart_contents as $cart_item_key => $values ) {
					$_product = $values['data'];
					$quantity = 0;
					if ( get_option( 'woocommerce_t4m_variations_separate', 'yes' ) == 'no' && $_product instanceof WC_Product_Variation && $_product->parent ) {
						$parent = $_product->parent;
						foreach ( $cart->cart_contents as $valuesInner ) {
							$p = $valuesInner['data'];
							if ( $p instanceof WC_Product_Variation && $p->parent && $p->parent->id == $parent->id ) {
								$quantity += $valuesInner['quantity'];
								$this->discount_coeffs[$_product->variation_id]['quantity'] = $quantity;
							}
						}
					} else {
						$quantity = $values['quantity'];
					}
					$this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] = dm_inv_get_discount( $_product, $quantity );
					$base_price = null;
					if (function_exists('is_offer_cart')) {
						if (is_offer_cart())
							$base_price = $_product->get_bid_price();
					}
					if (empty($base_price)) {
						if ($_product->is_type('fiztrade') || $_product->is_type('dealer'))
							$base_price = $_product->get_ask_price();
						else
							$base_price = $_product->get_price();
					}
					
					$this->discount_coeffs[$this->get_actual_id( $_product )]['orig_price'] = $base_price;
				}
			}

		}

		/**
		 * Filter product price so that the discount is visible during order viewing.
		 *
		 * @param $price
		 * @param $values
		 * @return string
		 */
		public function filter_subtotal_order_price( $price, $values, $order ) {

			if ( !$values || !$order ) {
				return $price;
			}
			if ( $this->coupon_check() ) {
				return $price;
			}

			$_product = get_product( $values['product_id'] );
			$actual_id = $values['product_id'];
			if ( $_product && $_product instanceof WC_Product_Variable && $values['variation_id'] ) {
				$actual_id = $values['variation_id'];
			}
			$discount_coeffs = $this->gather_discount_coeffs_from_order( $order->id );
			if ( empty( $discount_coeffs ) ) {
				return $price;
			}
			@$coeff = $discount_coeffs[$actual_id]['coeff'];
			if ( !$coeff ) {
				return $price;
			}
			
			$new_css = esc_attr( get_option( 'woocommerce_t4m_css_new_price', 'color: #4AB915; font-weight: bold;' ) );
			$bulk_info = sprintf( __( 'Incl. %s volume discount', 'wc_bulk_discount' ), woocommerce_price($coeff) );

			return "<span class='discount-info' title='$bulk_info'>" .
			"<span>$price</span>" .
			"<span class='new-price' style='$new_css'> ($bulk_info)</span></span>";

		}

		/**
		 * Gather discount information from order.
		 *
		 * @param $order_id
		 * @return array
		 */
		protected function gather_discount_coeffs_from_order( $order_id ) {

			$meta = get_post_meta( $order_id, '_woocommerce_t4m_discount_coeffs', true );

			if ( !$meta ) {
				return null;
			}

			$order_discount_coeffs = json_decode( $meta, true );
			return $order_discount_coeffs;

		}

		/**
		 * Hook to woocommerce_before_calculate_totals action.
		 *
		 * @param WC_Cart $cart
		 */
		public function action_before_calculate( WC_Cart $cart ) {
			
			if ( $this->coupon_check() ) {
				return;
			}

			if ($this->bulk_discount_calculated) {
				return;
			}

			$this->gather_discount_coeffs();

			if ( sizeof( $cart->cart_contents ) > 0 ) {
				
				global $bulk_discounts;
				global $total_discount;
				$bulk_discounts = array();
				$total_discount = 0;
				foreach ( $cart->cart_contents as $cart_item_key => $values ) {				
					$_product = $values['data'];
					
					if ($_product->is_type('fiztrade') || $_product->is_type('dealer')) {
						// DM products get prices differently, so we have to include the discount differently
						$discount = $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'];
						$values['data']->set_discount($discount / $values['quantity']);
					} else {
						$row_base_price = max( 0, $_product->get_price() * $values['quantity'] - ( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] ) );

						$bulk_discounts[$this->get_actual_id( $_product )] = $discount;
						$total_discount += $discount;
						$values['data']->set_price( $row_base_price );
					}
				}
				
				//add_filter('woocommerce_cart_subtotal', array($this, 'subtotal_filter'));
				//add_filter('woocommerce_calculated_total', function ($price) { global $total_discount; return $price - $total_discount; });
				//add_filter('woocommerce_get_discounted_price', array( $this, 'apply_line_discount' ), 10, 3);
				
				$this->bulk_discount_calculated = true;

			}

		}
		
		// only works for US-style numbers
		public function subtotal_filter ($price_html) { 
			global $total_discount;  
			$matches = array();
			preg_match( '/[0-9,]*\.[0-9,]+/', $price_html , $matches);
			$subtotal = str_replace(',', '', $matches[0]);
			
			return woocommerce_price(floatval($subtotal) - $total_discount); 
		}
		
		/*
		public function apply_line_discount ($price, $values, $cart) {
			global $bulk_discounts;
			
			$_product = $values['data'];
			if (array_key_exists($this->get_actual_id( $_product ), $bulk_discounts)) {
				return $price - $bulk_discounts[$this->get_actual_id( $_product )];
			} else {
				return $price;
			}
		}
		*/

		public function filter_before_calculate( $res ) {

			if ($this->bulk_discount_calculated) {
				return $res;
			}

			$cart = WC()->cart;

			if ( $this->coupon_check() ) {
				return $res;
			}

			$this->gather_discount_coeffs();

			if ( sizeof( $cart->cart_contents ) > 0 ) {

				foreach ( $cart->cart_contents as $cart_item_key => $values ) {
					$_product = $values['data'];
					$unit_price = ($_product->is_type('fiztrade') || $_product->is_type('dealer')) ? $_product->get_ask_price() : $_product->get_price();
					$row_base_price = max( 0, $unit_price * $values['quantity'] - ( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] ) );


					$values['data']->set_price( $row_base_price );
				}

				$this->bulk_discount_calculated = true;

			}

			return $res;

		}

		/**
		 * @param $product
		 * @return int
		 */
		protected function get_actual_id( $product ) {

			if ( $product instanceof WC_Product_Variation ) {
				return $product->variation_id;
			} else {
				return $product->id;
			}

		}

		/**
		 * Hook to woocommerce_calculate_totals.
		 *
		 * @param WC_Cart $cart
		 */
		public function action_after_calculate( WC_Cart $cart ) {

			if ( $this->coupon_check() ) {
				return;
			}

			if ( sizeof( $cart->cart_contents ) > 0 ) {
				foreach ( $cart->cart_contents as $cart_item_key => $values ) {
					$_product = $values['data'];
					$values['data']->set_price( $this->discount_coeffs[$this->get_actual_id( $_product )]['orig_price'] );
				}
			}

		}

		/**
		 * Show discount info in cart.
		 */
		public function before_cart_table() {

				echo "<div class='cart-show-discounts'>";
				echo get_option( 'woocommerce_t4m_cart_info' );
				echo "</div>";

		}

		/**
		 * Hook to woocommerce_cart_product_subtotal filter.
		 *
		 * @param $subtotal
		 * @param $_product
		 * @param $quantity
		 * @param WC_Cart $cart
		 * @return string
		 */
		public function filter_cart_product_subtotal( $subtotal, $_product, $quantity ) {

			if ( !$_product || !$quantity ) {
				return $subtotal;
			}
			if ( $this->coupon_check() ) {
				return $subtotal;
			}

			$coeff = $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'];
			$unit_price = ($_product->is_type('fiztrade') || $_product->is_type('dealer')) ? $_product->get_ask_price() : $_product->get_price();
			$newsubtotal = woocommerce_price( max( 0, ( $unit_price * $quantity - $coeff ) ) );

			return $newsubtotal;

		}

		/**
		 * Store discount info in order as well
		 *
		 * @param $order_id
		 */
		public function order_update_meta( $order_id ) {

			update_post_meta( $order_id, "_woocommerce_t4m_discount_type", get_option( 'woocommerce_t4m_discount_type', '' ) );
			update_post_meta( $order_id, "_woocommerce_t4m_discount_coeffs", json_encode( $this->discount_coeffs ) );

		}

		/**
		 * Display discount information in Product Detail.
		 */
		public function single_product_summary() {

			global $thepostid, $post;
			if ( !$thepostid ) $thepostid = $post->ID;

			echo "<div class='productinfo-show-discounts'>";
			echo get_post_meta( $thepostid, '_bulkdiscount_text_info', true );
			echo "</div>";

		}

		/**
		 * Add entry to Product Settings.
		 */
		public function action_product_write_panel_tabs() {

			$style = '';

			if ( version_compare( WOOCOMMERCE_VERSION, "2.1.0" ) >= 0 ) {
				$style = 'style = "padding: 10px !important"';
			}

			echo '<li class="bulkdiscount_tab bulkdiscount_options"><a href="#bulkdiscount_product_data" '.$style.'>' . __( 'Bulk Discount', 'wc_bulk_discount' ) . '</a></li>';

		}

		/**
		 * Add entry content to Product Settings.
		 */
		public function action_product_write_panels() {

			global $thepostid, $post;

			if ( !$thepostid ) $thepostid = $post->ID;
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function () {
					var e = jQuery( '#bulkdiscount_product_data' );
					<?php
					for($i = 1; $i <= 6; $i++) :
					?>
					e.find( '.block<?php echo $i; ?>' ).hide();
					e.find( '.options_group<?php echo max($i, 2); ?>' ).hide();
					e.find( '#add_discount_line<?php echo max($i, 2); ?>' ).hide();
					e.find( '#add_discount_line<?php echo $i; ?>' ).click( function () {
						if ( <?php echo $i; ?> == 1 || ( e.find( '#_bulkdiscount_quantity_<?php echo max($i-1, 1); ?>' ).val() != '' &&
							<?php if ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'flat' ) : ?>
							e.find( '#_bulkdiscount_discount_flat_<?php echo max($i-1, 1); ?>' ).val() != ''
						<?php else: ?>
						e.find( '#_bulkdiscount_discount_<?php echo max($i-1, 1); ?>' ).val() != ''
						<?php endif; ?>
						) )
						{
							e.find( '.block<?php echo $i; ?>' ).show( 400 );
							e.find( '.options_group<?php echo min($i+1, 6); ?>' ).show( 400 );
							e.find( '#add_discount_line<?php echo min($i+1, 5); ?>' ).show( 400 );
							e.find( '#add_discount_line<?php echo $i; ?>' ).hide( 400 );
							e.find( '#delete_discount_line<?php echo min($i+1, 6); ?>' ).show( 400 );
							e.find( '#delete_discount_line<?php echo $i; ?>' ).hide( 400 );
						}
						else
						{
							alert( '<?php _e( 'Please fill in the current line before adding new line.', 'wc_bulk_discount' ); ?>' );
						}
					} );
					e.find( '#delete_discount_line<?php echo max($i, 1); ?>' ).hide();
					e.find( '#delete_discount_line<?php echo $i; ?>' ).click( function () {
						e.find( '.block<?php echo max($i-1, 1); ?>' ).hide( 400 );
						e.find( '.options_group<?php echo min($i, 6); ?>' ).hide( 400 );
						e.find( '#add_discount_line<?php echo min($i, 5); ?>' ).hide( 400 );
						e.find( '#add_discount_line<?php echo max($i-1, 1); ?>' ).show( 400 );
						e.find( '#delete_discount_line<?php echo min($i, 6); ?>' ).hide( 400 );
						e.find( '#delete_discount_line<?php echo max($i-1, 2); ?>' ).show( 400 );
						e.find( '#_bulkdiscount_quantity_<?php echo max($i-1, 1); ?>' ).val( '' );
						<?php
							if ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'flat' ) :
						?>
						e.find( '#_bulkdiscount_discount_flat_<?php echo max($i-1, 1); ?>' ).val( '' );
						<?php else: ?>
						e.find( '#_bulkdiscount_discount_<?php echo max($i-1, 1); ?>' ).val( '' );
						<?php endif; ?>
					} );
					<?php
					endfor;
					for ($i = 1, $j = 2; $i <= 5; $i++, $j++) {
						$cnt = 1;
						if (get_post_meta($thepostid, "_bulkdiscount_quantity_$i", true) || get_post_meta($thepostid, "_bulkdiscount_quantity_$j", true)) {
							?>
					e.find( '.block<?php echo $i; ?>' ).show();
					e.find( '.options_group<?php echo $i; ?>' ).show();
					e.find( '#add_discount_line<?php echo $i; ?>' ).hide();
					e.find( '#delete_discount_line<?php echo $i; ?>' ).hide();
					e.find( '.options_group<?php echo min($i+1,6); ?>' ).show();
					e.find( '#add_discount_line<?php echo min($i+1,6); ?>' ).show();
					e.find( '#delete_discount_line<?php echo min($i+1,6); ?>' ).show();
					<?php
					$cnt++;
				}
			}
			if ($cnt >= 6) {
				?>e.find( '#add_discount_line6' ).show();
					<?php
			}
			?>
				} );
			</script>

			<div id="bulkdiscount_product_data" class="panel woocommerce_options_panel">

				<div class="options_group">
					<?php
					woocommerce_wp_checkbox( array( 'id' => '_bulkdiscount_enabled', 'value' => get_post_meta( $thepostid, '_bulkdiscount_enabled', true ) ? get_post_meta( $thepostid, '_bulkdiscount_enabled', true ) : 'yes', 'label' => __( 'Bulk Discount enabled', 'wc_bulk_discount' ) ) );
					woocommerce_wp_textarea_input( array( 'id' => "_bulkdiscount_text_info", 'label' => __( 'Bulk discount special offer text in product description', 'wc_bulk_discount' ), 'description' => __( 'Optionally enter bulk discount information that will be visible on the product page.', 'wc_bulk_discount' ), 'desc_tip' => 'yes', 'class' => 'fullWidth' ) );
					?>
				</div>

				<?php
				for ( $i = 1;
				      $i <= 5;
				      $i++ ) :
					?>

					<div class="options_group<?php echo $i; ?>">
						<a id="add_discount_line<?php echo $i; ?>" class="button-secondary"
						   href="#block<?php echo $i; ?>"><?php _e( 'Add discount line', 'wc_bulk_discount' ); ?></a>
						<a id="delete_discount_line<?php echo $i; ?>" class="button-secondary"
						   href="#block<?php echo $i; ?>"><?php _e( 'Remove last discount line', 'wc_bulk_discount' ); ?></a>

						<div class="block<?php echo $i; ?> <?php echo ( $i % 2 == 0 ) ? 'even' : 'odd' ?>">
							<?php
							woocommerce_wp_text_input( array( 'id' => "_bulkdiscount_quantity_$i", 'label' => __( 'Quantity (min.)', 'wc_bulk_discount' ), 'type' => 'number', 'description' => __( 'Enter the minimal quantity for which the discount applies.', 'wc_bulk_discount' ), 'custom_attributes' => array(
								'step' => '1',
								'min' => '1'
							) ) );
							if ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'flat' ) {
								woocommerce_wp_text_input( array( 'id' => "_bulkdiscount_discount_flat_$i", 'type' => 'number', 'label' => sprintf( __( 'Discount (%s)', 'wc_bulk_discount' ), get_woocommerce_currency_symbol() ), 'description' => sprintf( __( 'Enter the flat discount in %s.', 'wc_bulk_discount' ), get_woocommerce_currency_symbol() ), 'custom_attributes' => array(
									'step' => 'any',
									'min' => '0'
								) ) );
							} else {
								woocommerce_wp_text_input( array( 'id' => "_bulkdiscount_discount_$i", 'type' => 'number', 'label' => __( 'Discount (%)', 'wc_bulk_discount' ), 'description' => __( 'Enter the discount in percents (Allowed values: 0 to 100).', 'wc_bulk_discount' ), 'custom_attributes' => array(
									'step' => 'any',
									'min' => '0',
									'max' => '100'
								) ) );
							}
							?>
						</div>
					</div>

				<?php
				endfor;
				?>

				<div class="options_group6">
					<a id="delete_discount_line6" class="button-secondary"
					   href="#block6"><?php _e( 'Remove last discount line', 'wc_bulk_discount' ); ?></a>
				</div>

				<br/>

			</div>

		<?php
		}

		/**
		 * Enqueue frontend dependencies.
		 */
		public function action_enqueue_dependencies() {

			wp_register_style( 'woocommercebulkdiscount-style', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_style( 'woocommercebulkdiscount-style' );
			wp_enqueue_script( 'jquery' );

		}

		/**
		 * Enqueue backend dependencies.
		 */
		public function action_enqueue_dependencies_admin() {

			wp_register_style( 'woocommercebulkdiscount-style-admin', plugins_url( 'css/admin.css', __FILE__ ) );
			wp_enqueue_style( 'woocommercebulkdiscount-style-admin' );
			wp_enqueue_script( 'jquery' );

		}

		/**
		 * Updating post meta.
		 *
		 * @param $post_id
		 */
		public function action_process_meta( $post_id ) {

			if ( isset( $_POST['_bulkdiscount_text_info'] ) ) update_post_meta( $post_id, '_bulkdiscount_text_info', stripslashes( $_POST['_bulkdiscount_text_info'] ) );

			if ( isset( $_POST['_bulkdiscount_enabled'] ) && $_POST['_bulkdiscount_enabled'] == 'yes' ) {
				update_post_meta( $post_id, '_bulkdiscount_enabled', stripslashes( $_POST['_bulkdiscount_enabled'] ) );
			} else {
				update_post_meta( $post_id, '_bulkdiscount_enabled', stripslashes( 'no' ) );
			}

			for ( $i = 1; $i <= 5; $i++ ) {
				if ( isset( $_POST["_bulkdiscount_quantity_$i"] ) ) update_post_meta( $post_id, "_bulkdiscount_quantity_$i", stripslashes( $_POST["_bulkdiscount_quantity_$i"] ) );
				if ( ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'flat' ) ) {
					if ( isset( $_POST["_bulkdiscount_discount_flat_$i"] ) ) update_post_meta( $post_id, "_bulkdiscount_discount_flat_$i", stripslashes( $_POST["_bulkdiscount_discount_flat_$i"] ) );
				} else {
					if ( isset( $_POST["_bulkdiscount_discount_$i"] ) ) update_post_meta( $post_id, "_bulkdiscount_discount_$i", stripslashes( $_POST["_bulkdiscount_discount_$i"] ) );
				}
			}

		}

		/**
		 * @access public
		 * @return void
		 */
		public function add_tab() {

			foreach ( $this->settings_tabs as $name => $label ) {
				$class = 'nav-tab';
				if ( $this->current_tab == $name )
					$class .= ' nav-tab-active';
				echo '<a href="' . admin_url( 'admin.php?page=woocommerce&tab=' . $name ) . '" class="' . $class . '">' . $label . '</a>';
			}

		}

		/**
		 * @access public
		 * @return void
		 */
		public function settings_tab_action() {

			global $woocommerce_settings;

			// Determine the current tab in effect.
			$current_tab = $this->get_tab_in_view( current_filter(), 'woocommerce_settings_tabs_' );

			do_action( 'woocommerce_bulk_discount_settings' );

			// Display settings for this tab (make sure to add the settings to the tab).
			woocommerce_admin_fields( $woocommerce_settings[$current_tab] );

		}

		/**
		 * Save settings in a single field in the database for each tab's fields (one field per tab).
		 */
		public function save_settings() {

			global $woocommerce_settings;

			// Make sure our settings fields are recognised.
			$this->add_settings_fields();

			$current_tab = $this->get_tab_in_view( current_filter(), 'woocommerce_update_options_' );
			woocommerce_update_options( $woocommerce_settings[$current_tab] );

		}

		/**
		 * Get the tab current in view/processing.
		 */
		public function get_tab_in_view( $current_filter, $filter_base ) {

			return str_replace( $filter_base, '', $current_filter );

		}


		/**
		 * Add settings fields for each tab.
		 */
		public function add_settings_fields() {
			global $woocommerce_settings;

			// Load the prepared form fields.
			$this->init_form_fields();

			if ( is_array( $this->fields ) )
				foreach ( $this->fields as $k => $v )
					$woocommerce_settings[$k] = $v;
		}

		/**
		 * Prepare form fields to be used in the various tabs.
		 */
		public function init_form_fields() {

			// Define settings
			$this->fields['bulk_discount'] = apply_filters( 'woocommerce_bulk_discount_settings_fields', array(

				array( 'name' => __( 'Bulk Discount', 'wc_bulk_discount' ), 'type' => 'title', 'desc' => __( 'The following options are specific to product bulk discount.', 'wc_bulk_discount' ) . '<br /><br/><strong><i>' . __( 'After changing the settings, it is recommended to clear all sessions in WooCommerce &gt; System Status &gt; Tools.', 'wc_bulk_discount' ) . '</i></strong>', 'id' => 't4m_bulk_discounts_options' ),

				array(
					'name' => __( 'Bulk Discount globally enabled', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_enable_bulk_discounts',
					'desc' => __( '', 'wc_bulk_discount' ),
					'std' => 'yes',
					'type' => 'checkbox',
					'default' => 'yes'
				),

				array(
					'title' => __( 'Discount Type', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_discount_type',
					'desc' => sprintf( __( 'Select the type of discount. Percentage Discount deducts amount of %% from price while Flat Discount deducts fixed amount in %s', 'wc_bulk_discount' ), get_woocommerce_currency_symbol() ),
					'desc_tip' => true,
					'std' => 'yes',
					'type' => 'select',
					'css' => 'min-width:200px;',
					'class' => 'chosen_select',
					'options' => array(
						'' => __( 'Percentage Discount', 'wc_bulk_discount' ),
						'flat' => __( 'Flat Discount', 'wc_bulk_discount' )
					)
				),

				array(
					'name' => __( 'Treat product variations separately', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_variations_separate',
					'desc' => __( 'You need to have this option unchecked to apply discounts to variations by shared quantity.', 'wc_bulk_discount' ),
					'std' => 'yes',
					'type' => 'checkbox',
					'default' => 'yes'
				),

				array(
					'name' => __( 'Remove any bulk discounts if a coupon code is applied', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_remove_discount_on_coupon',
					'std' => 'yes',
					'type' => 'checkbox',
					'default' => 'yes'
				),

				array(
					'name' => __( 'Show discount information next to cart item price', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_show_on_item',
					'desc' => __( 'Applies only to percentage discount.', 'wc_bulk_discount' ),
					'std' => 'yes',
					'type' => 'checkbox',
					'default' => 'yes'
				),

				array(
					'name' => __( 'Show discount information next to item subtotal price', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_show_on_subtotal',
					'std' => 'yes',
					'type' => 'checkbox',
					'default' => 'yes'
				),

				array(
					'name' => __( 'Show discount information next to item subtotal price in order history', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_show_on_order_subtotal',
					'desc' => __( 'Includes showing discount in order e-mails and invoices.', 'wc_bulk_discount' ),
					'std' => 'yes',
					'type' => 'checkbox',
					'default' => 'yes'
				),

				array(
					'name' => __( 'Optionally enter information about discounts visible on cart page.', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_cart_info',
					'type' => 'textarea',
					'css' => 'width:100%; height: 75px;'
				),

				array(
					'name' => __( 'Optionally change the CSS for old price on cart before discounting.', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_css_old_price',
					'type' => 'textarea',
					'css' => 'width:100%;',
					'default' => 'color: #777; text-decoration: line-through; margin-right: 4px;'
				),

				array(
					'name' => __( 'Optionally change the CSS for new price on cart after discounting.', 'wc_bulk_discount' ),
					'id' => 'woocommerce_t4m_css_new_price',
					'type' => 'textarea',
					'css' => 'width:100%;',
					'default' => 'color: #4AB915; font-weight: bold;'
				),

				array( 'type' => 'sectionend', 'id' => 't4m_bulk_discounts_options' ),

				array(
					'desc' => 'If you find the WooCommerce Bulk Discount extension useful, please rate it <a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/woocommerce-bulk-discount#postform">&#9733;&#9733;&#9733;&#9733;&#9733;</a>.',
					'id' => 'woocommerce_t4m_bulk_discount_notice_text',
					'type' => 'title'
				),

				array( 'type' => 'sectionend', 'id' => 'woocommerce_t4m_bulk_discount_notice_text' )

			) ); // End settings

			$js = "
					jQuery('#woocommerce_t4m_enable_bulk_discounts').change(function() {

						jQuery('#woocommerce_t4m_cart_info, #woocommerce_t4m_variations_separate, #woocommerce_t4m_discount_type, #woocommerce_t4m_css_old_price, #woocommerce_t4m_css_new_price, #woocommerce_t4m_show_on_item, #woocommerce_t4m_show_on_subtotal, #woocommerce_t4m_show_on_order_subtotal').closest('tr').hide();

						if ( jQuery(this).attr('checked') ) {
							jQuery('#woocommerce_t4m_cart_info').closest('tr').show();
							jQuery('#woocommerce_t4m_variations_separate').closest('tr').show();
							jQuery('#woocommerce_t4m_discount_type').closest('tr').show();
							jQuery('#woocommerce_t4m_css_old_price').closest('tr').show();
							jQuery('#woocommerce_t4m_css_new_price').closest('tr').show();
							jQuery('#woocommerce_t4m_show_on_item').closest('tr').show();
							jQuery('#woocommerce_t4m_show_on_subtotal').closest('tr').show();
							jQuery('#woocommerce_t4m_show_on_order_subtotal').closest('tr').show();
						}

					}).change();

				";

			$this->run_js( $js );

		}

		/**
		 * Includes inline JavaScript.
		 *
		 * @param $js
		 */
		protected function run_js( $js ) {

			if ( function_exists( 'wc_enqueue_js' ) ) {
				wc_enqueue_js( $js );
			} else {
				WC()->add_inline_js( $js );
			}

		}

		/**
         * @return bool
		 */
		protected function coupon_check() {

			if ( get_option( 'woocommerce_t4m_remove_discount_on_coupon', 'yes' ) == 'no' ) return false;
			return !( empty( WC()->cart->applied_coupons ) );
		}

	}

	new Woo_Bulk_Discount_Plugin_t4m();

}