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
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'check_response' ) );
	}

	/**
	 * Checks the response from the WSU webservice gateway's use of our callback.
	 *
	 * @since 0.0.1
	 */
	public function check_response() {
		$order_id = absint( get_query_var( 'order-pay' ) );
		$order = wc_get_order( $order_id );

		// Invalid order.
		if ( false === $order ) {
			return;
		}

		$auth_id = '';
		if ( isset( $_GET['GUID'] ) ) {
			$auth_id = $_GET['GUID'];
		}
		$auth_array = explode( '-', $auth_id );

		if ( 36 !== strlen( $auth_id ) || 5 !== count( $auth_array ) ) {
			if ( 'pending' === $order->get_status() ) {
				// Order payment still needs to be collected.
				return;
			} else {
				// The order payment has already been processed.
				wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
				exit;
			}
		}

		$verify_guid = get_post_meta( $order_id, 'wsuws_request_guid', true );

		if ( $auth_id !== $verify_guid ) {
			$order->update_status( 'on-hold', 'The auth GUID did not match the original order.' );
			WSUWS_WooCommerce_Payment_Gateway::log( 'Stored GUID did not match response GUID: ' . sanitize_key( $auth_id ) . ' | ' . sanitize_key( $verify_guid ) );
			wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
			exit;
		}

		if ( 'pending' !== $order->get_status() ) {
			wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
			exit;
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
				$order->update_status( 'processing', 'Payment authorized.' );
				wc_reduce_stock_levels( $order->ID );

				// Empty the customer's cart.
				wc()->cart->empty_cart();
			} elseif ( 1 === $response->ReadPaymentAuthorizationResult->AuthorizationCPMReturnCode ) {
				// Bank error, card declined, etc...
				wc_add_notice( 'An error occured when processing payment.', 'error' );
			} else {
				// System error.
				wc_add_notice( 'A system error occured when processing payment.', 'error' );
			}
		} elseif ( 2 === $response->ReadPaymentAuthorizationResult->ReadReturnCode ) {
			WSUWS_WooCommerce_Payment_Gateway::log( 'GUID is already closed: ' . sanitize_key( $auth_id ) );
			wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
			exit;
		} else {
			WSUWS_WooCommerce_Payment_Gateway::log( 'Web service call failed. GUID: ' . sanitize_key( $auth_id ) . ' ReadReturnCode: ' . absint( $response->ReadPaymentAuthorizationResult->ReadReturnCode ) );
			return;
		}
	}
}
