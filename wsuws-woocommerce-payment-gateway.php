<?php
/*
Plugin Name: WSUWS WooCommerce Payment Gateway
Version: 0.0.1
Description: A WooCommerce payment gateway for WSU's webservice payment system.
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu/
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-WSUWS-WooCommerce-Payment-Gateway
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// The core plugin class.
require dirname( __FILE__ ) . '/includes/class-wsuws-woocommerce-payment-gateway.php';

add_action( 'after_setup_theme', 'WSUWS_WooCommerce_Payment_Gateway' );
/**
 * Start things up.
 *
 * @return \WSUWS_WooCommerce_Payment_Gateway
 */
function WSUWS_WooCommerce_Payment_Gateway() {
	return WSUWS_WooCommerce_Payment_Gateway::get_instance();
}
