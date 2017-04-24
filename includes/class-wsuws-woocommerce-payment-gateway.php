<?php

class WSUWS_WooCommerce_Payment_Gateway extends WC_Payment_Gateway {
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
	public static $csp_wsdl_url = 'https://dev-ewebservice.wsu.edu/CentralPaymentSite_WS/service.asmx?wsdl';

	/**
	 * WSUWS_WooCommerce_Payment_Gateway constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->id = 'wsuws_gateway';
		$this->method_title = 'WSU ITS Webservice';
		$this->method_description = 'Use the WSU webservice to process payments.';

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option( 'title' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initializes the fields displayed in the settings page for WSU ITS Webservice.
	 *
	 * @since 0.0.1
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => 'Enable/Disable',
				'type' => 'checkbox',
				'label' => 'Enable payments through WSU ITS',
				'default' => 'yes',
			),
			'title' => array(
				'title' => 'Title',
				'type' => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default' => 'Credit Card',
				'desc_tip' => true,
			),
			'description' => array(
				'title' => 'Customer Message',
				'type' => 'textarea',
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
		include_once dirname( __FILE__ ) . '/gateway-request.php';

		$order = wc_get_order( $order_id );

		return array(
			'result' => 'success',
			'redirect' => WSU\WSUWS_Woo_Gateway\request\get_request_url( $order ),
		);
	}

	/**
	 * Log a message to the WooCommerce logger.
	 *
	 * @since 0.0.1
	 *
	 * @param string $message
	 */
	public static function log( $message ) {
		if ( empty( self::$log ) ) {
			self::$log = new WC_Logger();
		}
		self::$log->add( 'wsuws', $message );
	}
}
