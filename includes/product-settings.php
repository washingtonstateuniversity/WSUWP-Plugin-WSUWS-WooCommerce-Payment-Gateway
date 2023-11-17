<?php namespace WSU\WSUWS_Woo_Gateway;


class Product_Settings {


	public static function init() {

		// The code for displaying WooCommerce Product Custom Fields
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'woocommerce_product_custom_fields' ) );

		// Following code Saves  WooCommerce Product Custom Fields
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'woocommerce_product_custom_fields_save' ) );

	}


	public static function woocommerce_product_custom_fields() {
		global $woocommerce, $post;
		echo '<div class="product_custom_field">';

		// Custom Product Text Field
		woocommerce_wp_text_input(
			array(
				'id'          => '_wsu_product_trans_type',
				'placeholder' => 'WSU Tran Type Code',
				'label'       => __('WSU Tran Type Code', 'woocommerce'),
				'desc_tip'    => 'true'
			)
		);
		echo '</div>';
	}


	public static function woocommerce_product_custom_fields_save( $post_id ) {
		// Custom Product Text Field
		$wsu_product_trans_type = $_POST['_wsu_product_trans_type'];


		if ( ! empty( $wsu_product_trans_type ) ) {

			update_post_meta( $post_id, '_wsu_product_trans_type', esc_attr( $wsu_product_trans_type ) );

		}
	}


}

Product_Settings::init();
