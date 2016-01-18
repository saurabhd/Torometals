<?php
/**
 * Customer new account admin email
 *
 * @author 		Imaginuity
 * @package 	Digital Metals/Templates/Emails/Plain
 * @version     2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo $email_heading . "\n\n";

echo sprintf( __( "A user has requested permission to trade metals on %s. The username is <strong>%s</strong>.", 'woocommerce' ), get_bloginfo('name'), $user_login ) . "\n\n";

echo "Visit the Customers page to view their information and approve the account. \n\n";

echo "\n****************************************************\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );