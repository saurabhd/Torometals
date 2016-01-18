<?php
/**
 * FizTrade submission failure email
 *
 * @author Imaginuity [JOB]
 * @package Digital Metals
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p>Submission to FizTrade of <a href="<?php echo admin_url('post.php?post='. $post_num .'&action=edit'); ?>">DG {trade] #{order_number}</a> has failed.  Please try submitting it again later.
If problems continue, please contact <a href="mailto:CustomerCare@fiztrade.com">CustomerCare@fiztrade.com</a>.</p>


<h2><?php printf( __( 'Order: %s', 'woocommerce'), $order->get_order_number() ); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order->order_date ) ), date_i18n( woocommerce_date_format(), strtotime( $order->order_date ) ) ); ?>)</h2>


<?php do_action( 'woocommerce_email_footer' ); ?>