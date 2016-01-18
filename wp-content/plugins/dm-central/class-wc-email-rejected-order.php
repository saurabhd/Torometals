<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Rejected Email
 *
 * An email sent to the customer when their order is rejected
 *
 * @class 		WC_Email_Rejected_Order
 * @version		2.0.0
 * @package		WooCommerce/Includes/Emails
 * @author 		Imaginuity [JOB]
 * @extends 	WC_Email
 */
class WC_Email_Rejected_Order extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {

		$this->id 				= 'rejected_order';
		$this->title 			= __( 'Rejected order', 'woocommerce' );
		$this->description		= __( 'Reject order emails are sent when a customer\'s order is rejected by an administrator.', 'woocommerce' );

		$this->heading 			= __( 'Rejected Order', 'woocommerce' );
		$this->subject      	= __( '[{blogname}] Order #{order_number} rejected', 'woocommerce' );

		$this->template_base    = dirname(__FILE__) .'/emails/';
		$this->template_html 	= 'customer-rejected-order.php';
		$this->template_plain 	= 'plain/customer-rejected-order.php';

		// Triggers for this email
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'trigger' ) );

		// Call parent constructor
		parent::__construct();
			
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
			$this->object 		= new WC_Order( $order_id );

			$this->find[] = '{order_number}';
			$this->replace[] = $this->object->id;
			
			$this->recipient = $this->object->billing_email;
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
			'order' 		=> $this->object,
			'email_heading' => $this->get_heading()
		),
		'', 
		dirname(__FILE__) . '/emails/');
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
			'order' 		=> $this->object,
			'email_heading' => $this->get_heading()
		),
		'', 
		dirname(__FILE__) . '/emails/');
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