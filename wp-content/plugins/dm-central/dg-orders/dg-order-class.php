<?php
require_once dirname(dirname(dirname(__FILE__))) . '/woocommerce/includes/abstracts/abstract-wc-order.php';

class DG_Order extends WC_Abstract_Order {
	public function __construct( $id = '' ) {
		parent::__construct($id);
		//echo 'total: '. $this->order_total;
	}
	
	/**
	 * Updates status of order - identical to parent except for commented code
	 *
	 * @access public
	 * @param string $new_status_slug Status to change the order to
	 * @param string $note (default: '') Optional note to add
	 * @return void
	 */
	public function update_status( $new_status, $note = '' ) {

		if ( ! $this->id ) {
			return;
		}

		// Standardise status names.
		$new_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		$old_status = $this->get_status();

		// Only update if they differ - and ensure post_status is a 'wc' status.
		if ( $new_status !== $old_status || ! in_array( $this->post_status, array_keys( wc_get_order_statuses() ) ) ) {

			// Update the order
			wp_update_post( array( 'ID' => $this->id, 'post_status' => 'wc-' . $new_status ) );
			$this->post_status = 'wc-' . $new_status;

			$this->add_order_note( trim( $note . ' ' . sprintf( __( 'Order status changed from %s to %s.', 'woocommerce' ), wc_get_order_status_name( $old_status ), wc_get_order_status_name( $new_status ) ) ) );

			// Status was changed
			do_action( 'woocommerce_order_status_' . $new_status, $this->id );
			do_action( 'woocommerce_order_status_' . $old_status . '_to_' . $new_status, $this->id );
			do_action( 'woocommerce_order_status_changed', $this->id, $old_status, $new_status );

			switch ( $new_status ) {

				case 'completed' :
					// Record the sales
					//$this->record_product_sales();

					// Increase coupon usage counts
					//$this->increase_coupon_usage_counts();

					// Record the completed date of the order
					update_post_meta( $this->id, '_completed_date', current_time('mysql') );

					// Update reports
					wc_delete_shop_order_transients( $this->id );
				break;

				case 'processing' :
				case 'on-hold' :
					// Record the sales
					//$this->record_product_sales();

					// Increase coupon usage counts
					//$this->increase_coupon_usage_counts();

					// Update reports
					wc_delete_shop_order_transients( $this->id );
				break;

				case 'cancelled' :
					// If the order is cancelled, restore used coupons
					//$this->decrease_coupon_usage_counts();

					// Update reports
					wc_delete_shop_order_transients( $this->id );
				break;
			}
		}
	}
	
	public function get_refunds() {
		return array();
	}
	
	public function get_total_refunded() {
		return 0;
	}
	
	public function get_qty_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		return 0;
	}
	
	public function get_total_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		return 0;
	}
	
	public function get_tax_refunded_for_item( $item_id, $tax_id, $item_type = 'line_item' ) {
		return 0;
	}
	
}
?>
