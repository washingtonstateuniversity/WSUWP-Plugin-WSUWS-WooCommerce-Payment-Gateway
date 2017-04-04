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

		if ( ! isset( $_GET['GUID'] ) ) { // @codingStandardsIgnoreLine
			wp_safe_redirect( esc_url( get_home_url() ) );
			exit;
		}

		$auth_id = $_GET['GUID']; // @codingStandardsIgnoreLine
		$auth_array = explode( '-', $auth_id );

		if ( 36 !== strlen( $auth_id ) || 5 !== count( $auth_array ) ) {
			WSUWS_WooCommerce_Payment_Gateway::log( 'Received an invalid auth GUID: ' . sanitize_key( $auth_id ) );
			wp_safe_redirect( esc_url( get_home_url() ) );
			exit;
		}

		$args = array(
			'post_type' => 'shop_order',
			'meta_query' => array(
				'key' => 'wsuws_request_guid',
				'value' => sanitize_key( $auth_id ),
			),
		);
		$order_query = new WP_Query( $args );

		$order = false;
		if ( $order_query->have_posts() ) {
			$order_query->the_post();
			$order = get_post();
		}
		wp_reset_postdata();

		if ( ! $order ) {
			WSUWS_WooCommerce_Payment_Gateway::log( 'No valid order found for this GUID: ' . sanitize_key( $auth_id ) );
			wp_safe_redirect( esc_url( get_home_url() ) );
		}

		$order = wc_get_order( $order );
		$client = new SoapClient( WSUWS_WooCommerce_Payment_Gateway::$csp_wsdl_url );

		$response = $client->ReadPaymentAuthorization( array(
			'PaymentAuthorizationGUID' => sanitize_key( $auth_id ),
		) );

		// @codingStandardsIgnoreStart
		if ( 0 === $response->ReadPaymentAuthorizationResult->ReadReturnCode ) {
			// Set authorized order to "processing" until shipment.
			$order->update_status( 'processing', 'Payment authorized.' );

			// Empty the customer's cart.
			wc()->cart->empty_cart();

			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		} elseif ( 9 === $response->ReadPaymentAuthorizationResult->ReadReturnCode ) {
			// Invalid authorization ID.
		}
		// @codingStandardsIgnoreEnd

		WSUWS_WooCommerce_Payment_Gateway::log( 'ReadPaymentAuthorization Response received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine
		exit;
	}
}
