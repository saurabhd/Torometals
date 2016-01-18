<?php
/**
 * Customer new account admin email
 *
 * @author 		Imaginuity
 * @package 	Digital Metals/Templates/Emails/HTML
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php printf(__("A user has requested permission to trade metals on %s. The username is <strong>%s</strong>.", 'woocommerce'), esc_html( get_bloginfo('name') ), esc_html( $user_login ) ); ?></p>

<p>Visit the <a href="<?php echo get_bloginfo('url') . '/wp-admin/admin.php?page=customers'; ?>">Customers</a> page to view their information and approve the account.</p>

<?php do_action( 'woocommerce_email_footer' ); ?>