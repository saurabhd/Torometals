<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Email_New_Customer' ) ) :

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

		$this->template_base	= trailingslashit(dirname(__FILE__));
		$this->template_html 	= 'emails/admin-new-account.php';
		$this->template_plain 	= 'emails/plain/admin-new-account.php';

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
	function trigger( $user_id, $user_pass = '', $password_generated = false ) {
	
		if ( $user_id ) {
			$this->object 		= new WP_User( $user_id );

			$this->user_pass          = $user_pass;
			$this->user_login         = stripslashes( $this->object->user_login );
			$this->user_email         = stripslashes( $this->object->user_email );
			$this->password_generated = $password_generated;

			$this->find['user_displayname'] = '{user_displayname}';
			$this->replace['user_displayname'] = $this->object->display_name;

			$this->recipient = $this->get_option( 'recipient' );

			if ( ! $this->recipient )
				$this->recipient = get_option( 'admin_email' );
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() || ! user_can($this->object, 'rpr_unverified') )
			return;
		
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
		wc_get_template( $this->template_html, array(
			'email_heading'      => $this->get_heading(),
			'user_login'         => $this->user_login,
			'user_pass'          => $this->user_pass,
			'blogname'           => $this->get_blogname(),
			'password_generated' => $this->password_generated,
			'sent_to_admin' => true,
			'plain_text'    => false
		),
		'', $this->template_base );
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
		wc_get_template( $this->template_plain, array(
			'email_heading'      => $this->get_heading(),
			'user_login'         => $this->user_login,
			'user_pass'          => $this->user_pass,
			'blogname'           => $this->get_blogname(),
			'password_generated' => $this->password_generated,
			'sent_to_admin' => true,
			'plain_text'    => true
		),
		'', $this->template_base );
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

endif;

return new WC_Email_New_Customer();