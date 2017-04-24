<?php
/*
Plugin Name: WSUWS WooCommerce Payment Gateway
Version: 0.0.19
Description: A WooCommerce payment gateway for WSU's webservice payment system.
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu/
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-WSUWS-WooCommerce-Payment-Gateway
*/

namespace WSU\WSUWS_Woo_Gateway;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'plugins_loaded', 'WSU\WSUWS_Woo_Gateway\load_gateway' );
/**
 * Loads the WSUWS payment gateway class, which extends WooCommerce.
 *
 * @since 0.0.1
 */
function load_gateway() {
	if ( class_exists( 'WC_Payment_Gateway' ) ) {
		require dirname( __FILE__ ) . '/includes/class-wsuws-woocommerce-payment-gateway.php';
		add_filter( 'woocommerce_payment_gateways', 'WSU\WSUWS_Woo_Gateway\add_gateway' );

		// Register the gateway's response handler.
		include_once dirname( __FILE__ ) . '/includes/class-wsuws-gateway-response.php';
		new \WSUWS_Gateway_Response();
	}
}

/**
 * Adds the WSUWS payment gateway to the list of offered payment gateways.
 *
 * @since 0.0.1
 *
 * @param array $methods
 *
 * @return array
 */
function add_gateway( $methods ) {
	$methods[] = 'WSUWS_WooCommerce_Payment_Gateway';
	return $methods;
}

add_action( 'woocommerce_order_status_on-hold_to_processing', 'WSU\WSUWS_Woo_Gateway\capture_payment' );
add_action( 'woocommerce_order_status_on-hold_to_completed', 'WSU\WSUWS_Woo_Gateway\capture_payment' );
/**
 * Captures a previously authorized payment when the order is changed from
 * on-hold status to "complete" or "processing".
 *
 * @since 0.0.15
 *
 * @param  int $order_id
 */
function capture_payment( $order_id ) {
	$order = wc_get_order( $order_id );
	$auth_id = get_post_meta( $order_id, 'wsuws_request_guid', true );

	$client = new \SoapClient( \WSUWS_WooCommerce_Payment_Gateway::$csp_wsdl_url );

	$auth_cap_response = $client->AuthCapResponse( array(
		'RequestGUID' => sanitize_key( $auth_id ),
	) );

	\WSUWS_WooCommerce_Payment_Gateway::log( 'AuthCapResponseResponse received: ' . print_r( $auth_cap_response, true ) ); // @codingStandardsIgnoreLine

	if ( 1 === $auth_cap_response->AuthCapResponseResponse->ResponseReturnCode || // Rec type or status is invalid?
	     2 === $auth_cap_response->AuthCapResponseResponse->ResponseReturnCode    // This transaction has been closed before.
	) {
		$order->update_status( 'failed', 'Payment capture failed: ' . esc_html( $auth_cap_response->AuthCapResponseResponse->ResponseReturnMessage ) );
		return;
	}

	$request = array(
		'RequestGUID' => sanitize_key( $auth_id ),
		'CaptureAmount' => $order->get_total(),
		'OneStepTranType' => apply_filters( 'wsuws_gateway_trantype', '' ),
	);
	$response = $client->CaptureRequest( $request );

	\WSUWS_WooCommerce_Payment_Gateway::log( 'CaptureRequestResponse received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine

	if ( 1 === $response->CaptureRequestResult->ResponseReturnCode || // Rec type or status is invalid for Capture.
	     2 === $response->CaptureRequestResult->ResponseReturnCode || // Transaction has been closed before.
	     9 === $response->CaptureRequestResult->ResponseReturnCode    // Cybersource capture error.
	) {
		$order->update_status( 'failed', 'Payment capture failed: ' . esc_html( $response->CaptureRequestResult->ResponseReturnMessage ) );
		return;
	}

	update_post_meta( $order->get_id(), 'wsuws_capture_guid', sanitize_key( $response->CaptureRequestResult->CaptureGUID ) );

	$order->add_order_note( 'Payment was captured.' );
}
