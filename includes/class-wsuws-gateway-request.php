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

		$args = $this->build_auth_request( $order );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Request arguments for order ' . $order->get_order_number() . ':' . print_r( $args, true ) ); // @codingStandardsIgnoreLine

		$response = $client->AuthRequest( $args );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Response received: ' . print_r( $response, true ) ); // @codingStandardsIgnoreLine

		$request_guid = $response->AuthRequestResult->RequestGUID;
		$redirect_url = $response->AuthRequestResult->WebPageURLAndGUID;

		if ( ! empty( $request_guid ) ) {
			update_post_meta( $order->get_id(), 'wsuws_request_guid', sanitize_key( $request_guid ) );
		}

		return $redirect_url;
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
	protected function build_auth_request( $order ) {
		$request = array(
			'MerchantID' => apply_filters( 'wsuws_gateway_merchant_id', '' ),
			'AuthorizationAmount' => $order->get_total(), // decimal, required
			'OneStepTranType' => apply_filters( 'wsuws_gateway_trantype', '' ),
			'ApplicationIDPrimary' => apply_filters( 'wsuws_gateway_application_id', '' ),
			'ReturnURL' => $order->get_checkout_payment_url(),
			'AuthorizationAttemptLimit' => 3,
			'EmailAddressDeptContact' => apply_filters( 'wsuws_gateway_contact_email', '' ),
			'CancelURL' => $order->get_cancel_order_url(),
			'PostBackURL' => esc_url( get_home_url( get_current_blog_id(), '/this-value-is-useless-but-we-have-to-include-it-anyway/' ) ),
		);

		return $request;
	}
}
