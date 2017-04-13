<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WSUWS_Gateway_Request
 *
 * Generates a request to be sent to WSU's webservice gateway.
 *
 * @since 0.0.1
 */
class WSUWS_Gateway_Request {

	/**
	 * WSUWS_Gateway_Request constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {}

	/**
	 * Retrieves the URL used to redirect a custom to the WSU webservice gateway
	 * credit card form.
	 *
	 * @since 0.0.1
	 *
	 * @param WC_Order $order
	 *
	 * @return string URL to redirect the customer to when an order is placed.
	 */
	public function get_request_url( $order ) {
		$client = new SoapClient( WSUWS_WooCommerce_Payment_Gateway::$csp_wsdl_url );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Client created: ' . print_r( $client, true ) ); // @codingStandardsIgnoreLine

		$args = $this->build_auth_request_with_address( $order );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Request arguments for order ' . $order->get_order_number() . ':' . print_r( $args, true ) ); // @codingStandardsIgnoreLine

		$response = $client->AuthRequestWithAddress( $args );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Response received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine

		$result = array(
			'return_code' => $response->AuthRequestWithAddressResult->RequestReturnCode,
			'return_message' => $response->AuthRequestWithAddressResult->RequestReturnMessage,
			'request_guid' => $response->AuthRequestWithAddressResult->RequestGUID,
			'redirect_url' => $response->AuthRequestWithAddressResult->WebPageURLAndGUID,
		);

		if ( ! empty( $result['request_guid'] ) ) {
			update_post_meta( $order->ID, 'wsuws_request_guid', sanitize_key( $result['request_guid'] ) );
		}

		WSUWS_WooCommerce_Payment_Gateway::log( 'Response from web service for order ' . $order->get_order_number() . ':' . print_r( $result, true ) ); // @codingStandardsIgnoreLine

		return $result['redirect_url'];
	}

	/**
	 * Builds the request arguments to use with the CC authorization.
	 *
	 * @since 0.0.1
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	protected function build_auth_request_with_address( $order ) {
		$request = array(
			'MerchantID' => apply_filters( 'wsuws_gateway_merchant_id', '' ),
			'AuthorizationAmount' => $order->order_total, // decimal, required
			'OneStepTranType' => apply_filters( 'wsuws_gateway_trantype', '' ),
			'ApplicationIDPrimary' => apply_filters( 'wsuws_gateway_application_id', '' ),
			'ReturnURL' => $order->get_checkout_order_received_url(),
			'AuthorizationAttemptLimit' => 3,
			'EmailAddressDeptContact' => apply_filters( 'wsuws_gateway_contact_email', '' ),
			'BillingAddress' => $order->billing_address_1 . ' ' . $order->billing_address_2,
			'BillingState' => $order->billing_state,
			'BillingZipCode' => $order->billing_postcode,
			'BillingCountry' => $order->billing_country,
			'PostBackURL' => esc_url( get_home_url( get_current_blog_id(), '/this-value-is-useless-but-we-have-to-include-it-anyway/' ) ),
		);

		return $request;
	}
}
