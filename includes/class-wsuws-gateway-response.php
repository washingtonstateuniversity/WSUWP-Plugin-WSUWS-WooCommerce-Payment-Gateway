<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles a response callback from WSU's webservice gateway.
 *
 * @since 0.0.1
 */
class WSUWS_Gateway_Response {

	/**
	 * WSUWS_Gateway_Response constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		add_action( 'woocommerce_api_wsuws_gateway_response', array( $this, 'check_response' ) );
	}

	/**
	 * Checks the response from the WSU webservice gateway's use of our callback.
	 *
	 * @since 0.0.1
	 */
	public function check_response() {
		WSUWS_WooCommerce_Payment_Gateway::log( 'Received a response callback from webservice gateway: ' . esc_html( print_r( $_POST, true ) ) ); // @codingStandardsIgnoreLine
		exit;
	}
}
