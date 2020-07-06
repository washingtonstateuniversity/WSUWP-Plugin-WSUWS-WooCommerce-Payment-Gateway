<?php namespace WSU\WSUWS_Woo_Gateway;


class Settings {

	public function init() {

		add_filter( 'woocommerce_get_sections_products', array( __CLASS__, 'add_integration_section' ) );

		add_filter( 'woocommerce_get_settings_payment', array( __CLASS__, 'add_mrktplace_settings' ), 1, 2 );

	}


	public static function add_integration_section( $sections ) {

		$sections['wsu_marketplace'] = __( 'WSU MarketPlace' );

		return $sections;

	}

	public static function add_mrktplace_settings( $settings, $current_section ) {

		if ( 'wsu_marketplace' === $current_section ) {

			$settings = array(
				array(
					'name' => 'WSU MarketPlace Merchant ID',
					'type' => 'text',
					'id'   => 'wsu_mrktplace_merchant_id',
					'desc' => 'Message to display on the notice',
					'desc_tip' => true,
				),
				array(
					'name' => 'WSU MarketPlace Trans Type',
					'type' => 'text',
					'id'   => 'wsu_mrktplace_trans_type',
					'desc' => 'Message to display on the notice',
					'desc_tip' => true,
				),
			);

			return $settings;

		} else {

			return $settings;

		}

	}

}

( new Settings )->init();
