<?php
/**
 * Customizer settings: home page hero and business contact details.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values for every theme option.
 *
 * @return array<string,string>
 */
function saxon_option_defaults() {
	return array(
		'hero_heading'   => __( 'Practical solutions that move your business forward', 'saxon' ),
		'hero_text'      => __( 'Saxon Enterprises helps organizations plan, build and run the systems they depend on, with clear advice and hands on delivery.', 'saxon' ),
		'hero_cta_label' => __( 'Explore our services', 'saxon' ),
		'hero_cta_url'   => '/services/',
		'hero_alt_label' => __( 'Talk to us', 'saxon' ),
		'hero_alt_url'   => '/contact/',
		'contact_phone'  => '',
		'contact_email'  => '',
		'contact_address' => '',
		'contact_hours'  => __( 'Monday to Friday, 9:00 to 17:00 ET', 'saxon' ),
	);
}

/**
 * Read a theme option with its default.
 *
 * @param string $key Option key.
 * @return string
 */
function saxon_option( $key ) {
	$defaults = saxon_option_defaults();
	return (string) get_theme_mod( 'saxon_' . $key, $defaults[ $key ] ?? '' );
}

/**
 * Register Customizer panels, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function saxon_customize_register( $wp_customize ) {
	$defaults = saxon_option_defaults();

	$wp_customize->add_section(
		'saxon_hero',
		array(
			'title'    => __( 'Home page hero', 'saxon' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_section(
		'saxon_contact',
		array(
			'title'       => __( 'Contact details', 'saxon' ),
			'description' => __( 'Shown in the footer and on the Contact page template. Leave a field empty to hide it.', 'saxon' ),
			'priority'    => 31,
		)
	);

	$fields = array(
		'hero_heading'    => array( 'saxon_hero', __( 'Heading', 'saxon' ), 'text', 'sanitize_text_field' ),
		'hero_text'       => array( 'saxon_hero', __( 'Intro text', 'saxon' ), 'textarea', 'sanitize_textarea_field' ),
		'hero_cta_label'  => array( 'saxon_hero', __( 'Primary button label', 'saxon' ), 'text', 'sanitize_text_field' ),
		'hero_cta_url'    => array( 'saxon_hero', __( 'Primary button link', 'saxon' ), 'url', 'esc_url_raw' ),
		'hero_alt_label'  => array( 'saxon_hero', __( 'Secondary button label', 'saxon' ), 'text', 'sanitize_text_field' ),
		'hero_alt_url'    => array( 'saxon_hero', __( 'Secondary button link', 'saxon' ), 'url', 'esc_url_raw' ),
		'contact_phone'   => array( 'saxon_contact', __( 'Phone', 'saxon' ), 'text', 'sanitize_text_field' ),
		'contact_email'   => array( 'saxon_contact', __( 'Email', 'saxon' ), 'email', 'sanitize_email' ),
		'contact_address' => array( 'saxon_contact', __( 'Address', 'saxon' ), 'textarea', 'sanitize_textarea_field' ),
		'contact_hours'   => array( 'saxon_contact', __( 'Business hours', 'saxon' ), 'text', 'sanitize_text_field' ),
	);

	foreach ( $fields as $key => $field ) {
		list( $section, $label, $type, $sanitize ) = $field;

		$wp_customize->add_setting(
			'saxon_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'saxon_' . $key,
			array(
				'label'   => $label,
				'section' => $section,
				'type'    => $type,
			)
		);
	}
}
add_action( 'customize_register', 'saxon_customize_register' );
