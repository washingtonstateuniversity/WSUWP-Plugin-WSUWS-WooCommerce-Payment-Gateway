<?php

namespace WSU\WSUWS_Woo_Gateway\request;

/**
 * Retrieves the URL used to redirect a custom to the WSU webservice gateway
 * credit card form.
 *
 * @since 0.0.1
 *
 * @param \WC_Order $order
 *
 * @return string URL to redirect the customer to when an order is placed.
 */
function get_request_url( $order ) {

	$client = new \SoapClient( \WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway::$csp_wsdl_url );

	$trans_type = get_option( 'wsu_mrktplace_trans_type' );
	$merchant_id = get_option( 'wsu_mrktplace_merchant_id' );

	$args = array(
		'MerchantID' => ( ! empty( $merchant_id ) ) ? $merchant_id : apply_filters( 'wsuws_gateway_merchant_id', '' ),
		'AuthorizationAmount' => $order->get_total(), // decimal, required
		'OneStepTranType' => ( ! empty( $trans_type ) ) ? $trans_type : apply_filters( 'wsuws_gateway_trantype', '' ),
		'ApplicationIDPrimary' => $order->get_billing_first_name(),
		'ApplicationIDSecondary' => $order->get_billing_last_name(),
		'ReturnURL' => $order->get_checkout_payment_url(),
		'AuthorizationAttemptLimit' => 3,
		'EmailAddressDeptContact' => apply_filters( 'wsuws_gateway_contact_email', '' ),
		'CancelURL' => $order->get_cancel_order_url(),
		'PostBackURL' => esc_url( get_home_url( get_current_blog_id(), '/this-value-is-useless-but-we-have-to-include-it-anyway/' ) ),
	);


	\WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway::log( 'Request arguments for order ' . $order->get_order_number() . ':' . print_r( $args, true ) ); // @codingStandardsIgnoreLine

	$response = $client->AuthRequest( $args );

	\WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway::log( 'Response received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine

	$request_guid = $response->AuthRequestResult->RequestGUID;
	$redirect_url = $response->AuthRequestResult->WebPageURLAndGUID;

	if ( ! empty( $request_guid ) ) {
		update_post_meta( $order->get_id(), 'wsuws_request_guid', sanitize_key( $request_guid ) );
		update_post_meta( $order->get_id(), 'wsuws_request_args', json_encode( $args ) );
	}

	return $redirect_url;
}

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
	$trans_type = get_option( 'wsu_mrktplace_trans_type' );

	// This order is being paid for with another payment method.
	if ( 'wsuws_gateway' !== $order->get_payment_method() ) {
		return;
	}

	// This order does not have a valid auth ID to use.
	if ( empty( $auth_id ) ) {
		$order->update_status( 'failed', 'No valid auth GUID found. Payment cannot be processed.' );
		return;
	}

	$client = new \SoapClient( \WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway::$csp_wsdl_url );

	$auth_cap_response = $client->AuthCapResponse( array(
		'RequestGUID' => sanitize_key( $auth_id ),
	) );

	\WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway::log( 'AuthCapResponseResponse received: ' . print_r( $auth_cap_response, true ) ); // @codingStandardsIgnoreLine

	if ( 1 === $auth_cap_response->AuthCapResponseResponse->ResponseReturnCode || // Rec type or status is invalid?
	     2 === $auth_cap_response->AuthCapResponseResponse->ResponseReturnCode    // This transaction has been closed before.
	) {
		$order->update_status( 'failed', 'Payment capture failed: ' . esc_html( $auth_cap_response->AuthCapResponseResponse->ResponseReturnMessage ) );
		return;
	}

	$request = array(
		'RequestGUID' => sanitize_key( $auth_id ),
		'CaptureAmount' => $order->get_total(),
		'OneStepTranType' => ( ! empty( $trans_type ) ) ? $trans_type : apply_filters( 'wsuws_gateway_trantype', '' ),
	);

	$response = $client->CaptureRequest( $request );

	\WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway::log( 'CaptureRequestResponse received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine


	update_post_meta( $order->get_id(), 'wsuws_capture_response', sanitize_text_field( print_r( $response, true ) ) );

	if ( 1 === $response->CaptureRequestResult->ResponseReturnCode || // Rec type or status is invalid for Capture.
	     2 === $response->CaptureRequestResult->ResponseReturnCode || // Transaction has been closed before.
	     9 === $response->CaptureRequestResult->ResponseReturnCode    // Cybersource capture error.
	) {
		$order->update_status( 'failed', 'Payment capture failed: ' . esc_html( $response->CaptureRequestResult->ResponseReturnMessage ) );
		return;
	}

	update_post_meta( $order->get_id(), 'wsuws_capture_request', json_encode( $request ) );

	update_post_meta( $order->get_id(), 'wsuws_capture_guid', sanitize_key( $response->CaptureRequestResult->CaptureGUID ) );

	$order->add_order_note( 'Payment captured successfully.' );
}
