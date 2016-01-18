<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * New Customer Email
 *
 * An email sent to the admin when a new customer wants to be approved
 *
 * @class 		WC_Email_New_Customer
 * @version		2.0.0
 * @package		WooCommerce/Includes/Emails
 * @author 		Imaginuity [JOB]
 * @extends 	WC_Email
 */
class WC_Email_New_Customer extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {

		$this->id 				= 'new_customer';
		$this->title 			= __( 'New customer', 'woocommerce' );
		$this->description		= __( 'New account emails are sent when a new customer wants to be approved.', 'woocommerce' );

		$this->heading 			= __( 'New customer', 'woocommerce' );
		$this->subject      	= __( '[{blogname}] New customer {user_displayname}', 'woocommerce' );

		$this->template_html 	= 'emails/admin-new-account.php';
		$this->template_plain 	= 'emails/plain/admin-new-account.php';

		// Triggers for this email
		add_action( 'woocommerce_new_customer_notification', array( $this, 'trigger' ) );

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
	function trigger( $user_id ) {
		global $woocommerce;

		if ( $user_id ) {
			$this->object 		= get_userdata( $user_id );

			$this->find[] = '{user_displayname}';
			$this->replace[] = $this->object->display_name;
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
			'user' 		=> $this->object,
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
			'user' 		=> $this->object,
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