<?php

class WSUWS_WooCommerce_Payment_Gateway extends WC_Payment_Gateway {
	/**
	 * @var WSUWS_WooCommerce_Payment_Gateway
	 */
	private static $instance;

	/**
	 * Maintain and return the one instance. Initiate hooks when
	 * called the first time.
	 *
	 * @since 0.0.1
	 *
	 * @return \WSUWS_WooCommerce_Payment_Gateway
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWS_WooCommerce_Payment_Gateway();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to include.
	 *
	 * @since 0.0.1
	 */
	public function setup_hooks() {}
}
