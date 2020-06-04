<?php namespace WSU\WSUWS_Woo_Gateway;


class Customizer {

	public function init() {

		add_action( 'customize_register', array( __CLASS__, 'add_customizer_options' ) );

	}


	public static function add_customizer_options( $wp_customize ) {

		$wp_customize->add_section(
			'wsuwp_ecommerce',
			array(
				'title'    => 'Ecommerce Settings',
				'priority' => 100,
			)
		);

		$wp_customize->add_setting(
			'wsu_mrktplace_trans_type',
			array(
				'default'           => '',
				'transport'         => 'refresh',
				'type'              => 'option',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_setting(
			'wsu_mrktplace_merchant_id',
			array(
				'default'           => '',
				'transport'         => 'refresh',
				'type'              => 'option',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Control(
				$wp_customize,
				'wsu_mrktplace_trans_type_control',
				array(
					'label'    => 'Trans Type',
					'section'  => 'wsuwp_ecommerce',
					'settings' => 'wsu_mrktplace_trans_type',
					'type'     => 'text',
				)
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Control(
				$wp_customize,
				'wsu_mrktplace_merchant_id_control',
				array(
					'label'    => 'Merchant ID',
					'section'  => 'wsuwp_ecommerce',
					'settings' => 'wsu_mrktplace_merchant_id',
					'type'     => 'text',
				)
			)
		);

	}

}

(new Customizer)->init();
