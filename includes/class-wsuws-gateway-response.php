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
		add_action( 'woocommerce_thankyou_wsuws_gateway', array( $this, 'check_response' ) );
	}

	/**
	 * Checks the response from the WSU webservice gateway's use of our callback.
	 *
	 * @since 0.0.1
	 *
	 * @param int $order_id
	 */
	public function check_response( $order_id ) {
		WSUWS_WooCommerce_Payment_Gateway::log( 'Received a response callback from webservice gateway: ' . esc_html( print_r( $_POST, true ) ) ); // @codingStandardsIgnoreLine

		$order = wc_get_order( $order_id );

		// If a GUID is not set, no response can be checked.
		if ( ! isset( $_GET['GUID'] ) ) { // @codingStandardsIgnoreLine
			return;
		}

		// If an order is past the point of authorization, then we should not be running this again.
		$skip_order_status = array( 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );
		if ( in_array( $order->get_status(), $skip_order_status, true ) ) {
			return;
		}

		$auth_id = $_GET['GUID']; // @codingStandardsIgnoreLine
		$auth_array = explode( '-', $auth_id );

		if ( 36 !== strlen( $auth_id ) || 5 !== count( $auth_array ) ) {
			WSUWS_WooCommerce_Payment_Gateway::log( 'Received an invalid auth GUID: ' . sanitize_key( $auth_id ) );
			return;
		}

		$verify_guid = get_post_meta( $order_id, 'wsuws_request_guid', true );

		if ( $auth_id !== $verify_guid ) {
			$order->update_status( 'on-hold', 'The auth GUID did not match the original order.' );
			WSUWS_WooCommerce_Payment_Gateway::log( 'Stored GUID did not match response GUID: ' . sanitize_key( $auth_id ) . ' | ' . sanitize_key( $verify_guid ) );
			return;
		}

		$client = new SoapClient( WSUWS_WooCommerce_Payment_Gateway::$csp_wsdl_url );

		$response = $client->ReadPaymentAuthorization( array(
			'PaymentAuthorizationGUID' => sanitize_key( $auth_id ),
		) );

		WSUWS_WooCommerce_Payment_Gateway::log( 'ReadPaymentAuthorization Response received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine

		/**
		 * Handle the possible response return codes from the payment authorization:
		 *
		 * 0 -> Web service call was successful.
		 * 2 -> GUID is already closed.
		 * 9 (else) -> Web service call failed. (Likely an invalid GUID)
		 */
		if ( 0 === $response->ReadPaymentAuthorizationResult->ReadReturnCode ) {
			if ( 0 === $response->ReadPaymentAuthorizationResult->AuthorizationCPMReturnCode ) {
				// Set authorized order to "on-hold" until charged and shipped.
				$order->update_status( 'on-hold', 'Payment authorized.' );
				wc_reduce_stock_levels( $order->ID );

				// Empty the customer's cart.
				wc()->cart->empty_cart();
			} elseif ( 1 === $response->ReadPaymentAuthorizationResult->AuthorizationCPMReturnCode ) {
				// Bank error, card declined, etc...
			} else {
				// System error.
			}
		} elseif ( 2 === $response->ReadPaymentAuthorizationResult->ReadReturnCode ) {
			WSUWS_WooCommerce_Payment_Gateway::log( 'GUID is already closed: ' . sanitize_key( $auth_id ) );
			return;
		} else {
			WSUWS_WooCommerce_Payment_Gateway::log( 'Web service call failed. GUID: ' . sanitize_key( $auth_id ) . ' ReadReturnCode: ' . absint( $response->ReadPaymentAuthorizationResult->ReadReturnCode ) );
			return;
		}
	}
}
