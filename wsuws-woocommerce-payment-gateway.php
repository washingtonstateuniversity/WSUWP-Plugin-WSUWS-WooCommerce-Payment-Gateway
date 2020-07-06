<?php
/*
Plugin Name: WSUWS WooCommerce Payment Gateway
Version: 1.3.0
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

add_action( 'plugins_loaded', 'WSU\WSUWS_Woo_Gateway\bootstrap' );
/**
 * Loads the WSUWS payment gateway class, which extends WooCommerce.
 *
 * @since 0.0.1
 */
function bootstrap() {
	if ( class_exists( 'WC_Payment_Gateway' ) ) {
		require dirname( __FILE__ ) . '/includes/class-payment-gateway.php';
		add_filter( 'woocommerce_payment_gateways', array( '\WSU\WSUWS_Woo_Gateway\Gateway\Payment_Gateway', 'add_gateway' ) );
	}

	include_once dirname( __FILE__ ) . '/includes/gateway-response.php';
	include_once dirname( __FILE__ ) . '/includes/gateway-request.php';
	include_once dirname( __FILE__ ) . '/includes/include-settings.php';

	add_action( 'woocommerce_order_status_on-hold_to_processing', '\WSU\WSUWS_Woo_Gateway\request\capture_payment' );
	add_action( 'woocommerce_order_status_on-hold_to_completed', '\WSU\WSUWS_Woo_Gateway\request\capture_payment' );
}
