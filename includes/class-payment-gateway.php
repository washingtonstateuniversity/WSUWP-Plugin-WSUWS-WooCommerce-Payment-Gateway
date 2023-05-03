<?php

// https://rudrastyh.com/woocommerce/payment-gateway-plugin.html
// https://stackoverflow.com/questions/38193531/how-can-i-do-a-redirect-from-the-process-payment-function-in-a-custom-gateway-in
// https://stackoverflow.com/questions/31499251/woocommerce-submit-to-payment-gateway

namespace WSU\WSUWS_Woo_Gateway\Gateway;

class Payment_Gateway extends \WC_Payment_Gateway {
	/**
	 * Contains the logger for the current request.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	public static $log = '';

	/**
	 * The WSDL URL used to make SOAP requests for CC authorization.
	 *
	 * @since 0.0.4
	 *
	 * @var string
	 */
	public static $csp_wsdl_url = 'https://ewebservice.wsu.edu/CentralPaymentSite_WS/service.asmx?wsdl';

	/**
	 * WSUWS_WooCommerce_Payment_Gateway constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->id                 = 'wsuws_gateway';
		$this->method_title       = 'WSU ITS Webservice';
		$this->method_description = 'Use the WSU webservice to process payments.';

		$this->init_form_fields();
		$this->init_settings();

		$this->title    = $this->get_option( 'title' );
		$this->test_url = $this->get_option( 'test_url' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'pay_for_order' ) );
	}

	/**
	 * Initializes the fields displayed in the settings page for WSU ITS Webservice.
	 *
	 * @since 0.0.1
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'     => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable payments through WSU ITS',
				'default' => 'yes',
			),
			'title'       => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'Credit Card',
				'desc_tip'    => true,
			),
			'test_url'    => array(
				'title'       => 'Test URL',
				'type'        => 'text',
				'description' => 'TouchNet UPay testing url.',
				'default'     => '',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'   => 'Customer Message',
				'type'    => 'textarea',
				'default' => '',
			),
		);
	}

	/**
	 * Processes a payment request for an order.
	 *
	 * @since 0.0.1
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	public function pay_for_order( $order_id ) {
		$order = wc_get_order( $order_id );

		$this->log( 'Called: Pay_For_Order.' );

		echo '<p>' . __( 'Redirecting to payment provider...', 'txtdomain' ) . '</p>';
		// add a note to show order has been placed and the user redirected
		$order->add_order_note( __( 'Order placed and user redirected.', 'txtdomain' ) );
		// update the status of the order should need be
		// $order->update_status( 'on-hold', __( 'Awaiting payment.', 'txtdomain' ) );
		// remember to empty the cart of the user
		// WC()->cart->empty_cart();

		// perform a click action on the submit button of the form you are going to return
		wc_enqueue_js( 'setTimeout(function(){jQuery( "#submit-form" ).click();}, 3000);' );

		// return your form with the needed parameters
		echo '<form action="https://test.secure.touchnet.net:8443/C20607test_upay/web/index.jsp" method="post" target="_top">
				<input type="hidden" name="UPAY_SITE_ID" value="0">
				<input type="hidden" name="EXT_TRANS_ID" value="' . $order->id . '">
				<input type="hidden" name="AMT" value="' . $order->total . '">
				<input type="hidden" name="SUCCESS_LINK_TEXT" value="Complete Order">
				<input type="hidden" name="SUCCESS_LINK" value="' . $order->get_checkout_order_received_url() . '">
				<input type="hidden" name="CANCEL_LINK" value="' . $order->get_cancel_order_url() . '">
				<div class="btn-submit-payment" style="display: none;">
					<button type="submit" id="submit-form"></button>
				</div>
				</form>';
	}
			// <input type="hidden" name="SUCCESS_LINK" value="' . $order->get_checkout_payment_url( true ) . '">


	/**
	 * Log a message to the WooCommerce logger.
	 *
	 * @since 0.0.1
	 *
	 * @param string $message
	 */
	public static function log( $message ) {
		if ( empty( self::$log ) ) {
			self::$log = new \WC_Logger();
		}
		self::$log->add( 'wsuws', $message );
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
	public static function add_gateway( $methods ) {
		$methods[] = 'WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway';
		return $methods;
	}
}
