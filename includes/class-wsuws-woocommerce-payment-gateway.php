<?php

class WSUWS_WooCommerce_Payment_Gateway extends WC_Payment_Gateway {
	public function __construct() {
		$this->id = 'wsuws_gateway';
		$this->method_title = 'WSU ITS Webservice';
		$this->method_description = 'Use the WSU webservice to process payments.';

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option( 'title' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initializes the fields displayed in the settings page for WSU ITS Webservice.
	 *
	 * @since 0.0.1
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => 'Enable/Disable',
				'type' => 'checkbox',
				'label' => 'Enable payments through WSU ITS',
				'default' => 'yes',
			),
			'title' => array(
				'title' => 'Title',
				'type' => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default' => 'Credit Card',
				'desc_tip' => true,
			),
			'description' => array(
				'title' => 'Customer Message',
				'type' => 'textarea',
				'default' => '',
			),
		);
	}
}
