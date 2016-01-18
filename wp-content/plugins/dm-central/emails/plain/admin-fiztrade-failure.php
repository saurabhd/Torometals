<?php
/**
 * FizTrade submission failure email (plain text)
 *
 * @author Imaginuity [JOB]
 * @package Digital Metals
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo $email_heading . "\n\n";

echo __( "Submission to FizTrade of DG {trade] #{order_number} has failed.  Please try submitting it again later.
If problems continue, please contact CustomerCare@fiztrade.com.", 'woocommerce' );

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );