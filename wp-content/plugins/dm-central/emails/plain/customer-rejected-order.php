<?php
/**
 * Customer rejected order email (plain text)
 *
 * @author Imaginuity
 * @package Digital Metals/Light Theme
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo $email_heading . "\n\n";

echo __( "We're sorry but this order has been rejected:", 'woocommerce' );

echo "****************************************************\n\n";

do_action( 'woocommerce_email_before_order_table', $order, true );

echo sprintf( __( 'Order number: %s', 'woocommerce'), $order->get_order_number() ) . "\n";
echo sprintf( __( 'Order date: %s', 'woocommerce'), date_i18n( __( 'jS F Y', 'woocommerce' ), strtotime( $order->order_date ) ) ) . "\n";

do_action( 'woocommerce_email_order_meta', $order, true, true );

echo "\n" . $order->email_order_items_table( false, true, '', '', '', true );

echo "----------\n\n";

if ( $totals = $order->get_order_item_totals() ) {
	foreach ( $totals as $total ) {
		echo $total['label'] . "\t " . $total['value'] . "\n";
	}
}

echo "\n****************************************************\n\n";

do_action( 'woocommerce_email_after_order_table', $order, true, true );

echo "\n****************************************************\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );