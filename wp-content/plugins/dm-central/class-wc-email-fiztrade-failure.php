<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * FizTrade Failure Email
 *
 * An email sent to the admin when a FizTrade trade fails
 *
 * @class 		WC_Email_FizTrade_Failure
 * @version		2.0.0
 * @package		WooCommerce/Includes/Emails
 * @author 		Imaginuity [JOB]
 * @extends 	WC_Email
 */
class WC_Email_FizTrade_Failure extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {

		$this->id 				= 'fiztrade_failure';
		$this->title 			= __( 'FizTrade Failure', 'woocommerce' );
		$this->description		= __( 'FizTrade failure emails are sent when an order or offer that is auto-forwarded to FizTrade fails.', 'woocommerce' );

		$this->heading 			= __( 'FizTrade Failure', 'woocommerce' );
		$this->subject      	= __( '[{blogname}] FizTrade {trade} Failure', 'woocommerce' );

		$this->template_html 	= 'emails/admin-fiztrade-failure.php';
		$this->template_plain 	= 'emails/plain/admin-fiztrade-failure.php';

		// Triggers for this email
		add_action( 'dg_trade_failure', array( $this, 'trigger' ) );

		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->recipient = $this->get_option( 'recipient' );

		if ( ! $this->recipient )
			$this->recipient = get_option( 'admin_email' );
			
		// TODO: should we provide an option for this?
		$this->enabled = true;
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $order_id ) {
		global $woocommerce;

		if ( $order_id ) {
			$post_type = get_post_type($order_id);
			
			if ($post_type == 'dg_order') {
				$this->object 		= new DG_Order( $order_id );
				$this->find[] = '{trade}';
				$this->replace[] = 'Order';
			} else {
				$this->object 		= new DG_Offer( $order_id );
				$this->find[] = '{trade}';
				$this->replace[] = 'Offer';
			}

			$this->find[] = '{order_date}';
			$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );

			$this->find[] = '{order_number}';
			$this->replace[] = $this->object->get_order_number();
		}		

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
			return;
		//wp_die('sending email "' . $this->get_subject() .'" to '. $this->get_recipient());
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		woocommerce_get_template( $this->template_html, array(
			'post_num' 		=> $this->object->id,
			'email_heading' => $this->get_heading()
		) );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		woocommerce_get_template( $this->template_plain, array(
			'post_num' 		=> $this->object->id,
			'email_heading' => $this->get_heading()
		) );
		return ob_get_clean();
	}

    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable this email notification', 'woocommerce' ),
				'default' 		=> 'yes'
			),
			'recipient' => array(
				'title' 		=> __( 'Recipient(s)', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'woocommerce' ), esc_attr( get_option('admin_email') ) ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'subject' => array(
				'title' 		=> __( 'Subject', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce' ), $this->subject ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'heading' => array(
				'title' 		=> __( 'Email Heading', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce' ), $this->heading ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'email_type' => array(
				'title' 		=> __( 'Email type', 'woocommerce' ),
				'type' 			=> 'select',
				'description' 	=> __( 'Choose which format of email to send.', 'woocommerce' ),
				'default' 		=> 'html',
				'class'			=> 'email_type',
				'options'		=> array(
					'plain'		 	=> __( 'Plain text', 'woocommerce' ),
					'html' 			=> __( 'HTML', 'woocommerce' ),
					'multipart' 	=> __( 'Multipart', 'woocommerce' ),
				)
			)
		);
    }
}