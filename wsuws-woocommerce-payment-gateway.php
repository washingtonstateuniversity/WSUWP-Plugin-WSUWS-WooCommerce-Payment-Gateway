<?php
/*
Plugin Name: WSUWS WooCommerce Payment Gateway
Version: 0.0.13
Description: A WooCommerce payment gateway for WSU's webservice payment system.
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu/
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-WSUWS-WooCommerce-Payment-Gateway
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'plugins_loaded', 'wsuwp_load_wsuws_woocommerce_payment_gateway' );
/**
 * Loads the WSUWS payment gateway class, which extends WooCommerce.
 *
 * @since 0.0.1
 */
function wsuwp_load_wsuws_woocommerce_payment_gateway() {
	if ( class_exists( 'WC_Payment_Gateway' ) ) {
		require dirname( __FILE__ ) . '/includes/class-wsuws-woocommerce-payment-gateway.php';
		add_filter( 'woocommerce_payment_gateways', 'wsuwp_add_wsuws_woocommerce_payment_gateway' );

		// Register the gateway's response handler.
		include_once dirname( __FILE__ ) . '/includes/class-wsuws-gateway-response.php';
		new WSUWS_Gateway_Response();
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
function wsuwp_add_wsuws_woocommerce_payment_gateway( $methods ) {
	$methods[] = 'WSUWS_WooCommerce_Payment_Gateway';
	return $methods;
}
