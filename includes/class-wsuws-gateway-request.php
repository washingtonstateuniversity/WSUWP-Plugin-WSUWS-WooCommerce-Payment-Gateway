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
	 * The WSDL URL used to make SOAP requests for CC authorization.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $csp_wsdl_url = 'https://test-ewebservice.wsu.edu/CentralPaymentSite_WS/service.asmx?wsdl';

	/**
	 * URL to be used for callback when a request is complete.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * WSUWS_Gateway_Request constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->notify_url = WC()->api_request_url( 'wsuws_gateway_response' );
	}

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
		$client = new SoapClient( $this->csp_wsdl_url );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Client created: ' . print_r( $client, true ) );

		$args = $this->build_auth_request_with_address( $order );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Request arguments for order ' . $order->get_order_number() . ':' . print_r( $args, true ) ); // @codingStandardsIgnoreLine

		$response = $client->AuthRequestWithAddress( $args );

		WSUWS_WooCommerce_Payment_Gateway::log( 'Response received: ' . print_r( $response, true ) );

		// @codingStandardsIgnoreStart
		$result = array(
			'return_code' => $response->AuthRequestWithAddressResult->RequestReturnCode,
			'return_message' => $response->AuthRequestWithAddressResult->RequestReturnMessage,
			'request_guid' => $response->AuthRequestWithAddressResult->RequestGUID,
			'redirect_url' => $response->AuthRequestWithAddressResult->WebPageURLAndGUID,
		);
		// @codingStandardsIgnoreEnd

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
			'MerchantID' => '',
			'AuthorizationAmount' => $order->order_total, // decimal, required
			'OneStepTranType' => '',
			'ApplicationIDPrimary' => '',
			'ReturnURL' => $this->notify_url,
			'AuthorizationAttemptLimit' => 3,
			'EmailAddressDeptContact' => 'jeremy.felt@wsu.edu',
			'BillingAddress' => $order->billing_address_1 . ' ' . $order->billing_address_2,
			'BillingState' => $order->billing_state,
			'BillingZipCode' => $order->billing_postcode,
			'BillingCountry' => $order->billing_country,
			'PostBackURL' => $this->notify_url,
		);

		return $request;
	}
}
