<?php namespace WSU\WSUWS_Woo_Gateway\Postback;

class Postback_Handler {

	public static function handle_postback( \WP_REST_Request $request ) {

		$UPAY_SITE_ID = $_POST['UPAY_SITE_ID'];
		$EXT_TRANS_ID = $_POST['EXT_TRANS_ID']; // WC transaction id
		$posting_key  = $_POST['posting_key'];
		$tpg_trans_id = $_POST['tpg_trans_id'];
		$pmt_status   = $_POST['pmt_status']; // "success" or "cancelled"
		$pmt_amt      = $_POST['pmt_amt'];
		$pmt_date     = $_POST['pmt_date'];

		$order = wc_get_order( $EXT_TRANS_ID );
		$order->update_status( 'completed', 'Payment authorized.' );

		var_dump( $order );
	}


	public static function init() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'payment-api/v1',
					'/postback',
					array(
						'methods'             => \WP_REST_Server::CREATABLE,
						'callback'            => array( __CLASS__, 'handle_postback' ),
						'permission_callback' => '__return_true',
					)
				);
			}
		);
	}

}


Postback_Handler::init();
