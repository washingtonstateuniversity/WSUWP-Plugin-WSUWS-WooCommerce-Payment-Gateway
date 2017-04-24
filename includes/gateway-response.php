<?php

namespace WSU\WSUWS_Woo_Gateway\response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'before_woocommerce_pay', 'WSU\WSUWS_Woo_Gateway\response\check_response' );
/**
 * Checks the response from the WSU webservice gateway's use of our callback.
 *
 * @since 0.0.1
 */
function check_response() {
	$order_id = absint( get_query_var( 'order-pay' ) );
	$order = wc_get_order( $order_id );

	// Invalid order.
	if ( false === $order ) {
		\WSUWS_WooCommerce_Payment_Gateway::log( 'Order ' . $order_id . ' does not exist.' );
		return;
	}

	$auth_id = '';
	if ( isset( $_GET['GUID'] ) ) { // @codingStandardsIgnoreLine
		$auth_id = $_GET['GUID']; // @codingStandardsIgnoreLine
	}
	$auth_array = explode( '-', $auth_id );

	if ( 36 !== strlen( $auth_id ) || 5 !== count( $auth_array ) ) {
		$order->add_order_note( 'An invalid GUID was used when attempting to check authorization.' );
		\WSUWS_WooCommerce_Payment_Gateway::log( 'Invalid GUID: ' . $auth_id );
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
		$order->update_status( 'failed', 'The auth GUID did not match the original order.' );
		\WSUWS_WooCommerce_Payment_Gateway::log( 'Stored GUID did not match response GUID: ' . sanitize_key( $auth_id ) . ' | ' . sanitize_key( $verify_guid ) );
		wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
		exit;
	}

	if ( 'pending' !== $order->get_status() ) {
		wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
		exit;
	}

	$client = new \SoapClient( \WSUWS_WooCommerce_Payment_Gateway::$csp_wsdl_url );

	$response = $client->ReadPaymentAuthorization( array(
		'PaymentAuthorizationGUID' => sanitize_key( $auth_id ),
	) );

	\WSUWS_WooCommerce_Payment_Gateway::log( 'ReadPaymentAuthorization Response received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine

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
			wc_reduce_stock_levels( $order->get_id() );

			// Empty the customer's cart.
			wc()->cart->empty_cart();
			wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
			exit;
		} elseif ( 1 === $response->ReadPaymentAuthorizationResult->AuthorizationCPMReturnCode ) {
			// Bank error, card declined, etc...
			$order->add_order_note( 'An error occurred when processing payment.' );
			wc_add_notice( 'An error occurred when processing payment.', 'error' );
		} else {
			// System error.
			$order->add_order_note( 'A system error occurred when processing payment.' );
			wc_add_notice( 'A system error occured when processing payment.', 'error' );
		}
	} elseif ( 2 === $response->ReadPaymentAuthorizationResult->ReadReturnCode ) {
		\WSUWS_WooCommerce_Payment_Gateway::log( 'GUID is already closed: ' . sanitize_key( $auth_id ) );
		wp_safe_redirect( esc_url( $order->get_checkout_order_received_url() ) );
		exit;
	} else {
		\WSUWS_WooCommerce_Payment_Gateway::log( 'Web service call failed. GUID: ' . sanitize_key( $auth_id ) . ' ReadReturnCode: ' . absint( $response->ReadPaymentAuthorizationResult->ReadReturnCode ) );
		return;
	}
}
