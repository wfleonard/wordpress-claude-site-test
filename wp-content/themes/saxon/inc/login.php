<?php
/**
 * Saxon branding on the login, registration and password screens.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

/**
 * Logo URL: the Customizer logo when set, otherwise the logo shipped with the theme.
 *
 * @return string
 */
function saxon_login_logo_url() {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$url     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	return $url ? $url : SAXON_URI . '/assets/images/saxon-enterprises-logo.png';
}

/**
 * Replace the WordPress logo and match the site's colors.
 */
function saxon_login_styles() {
	$css = sprintf(
		'body.login{background:#f5f7fa;}
		.login h1 a{background-image:url(%s);background-size:contain;background-position:center;width:260px;max-width:100%%;height:150px;margin-bottom:16px;}
		.login #login{width:340px;max-width:calc(100%% - 32px);padding-top:6vh;}
		.login form{border:1px solid #d6dce4;border-top:4px solid #9b2c33;border-radius:8px;box-shadow:0 6px 18px rgb(16 32 56 / 10%%);}
		.wp-core-ui .button-primary{background:#9b2c33;border-color:#9b2c33;}
		.wp-core-ui .button-primary:hover,.wp-core-ui .button-primary:focus{background:#7d2329;border-color:#7d2329;}
		.login #nav a,.login #backtoblog a{color:#4a5565;}
		.login #nav a:hover,.login #backtoblog a:hover,.login a:focus{color:#9b2c33;}
		.login input[type=text]:focus,.login input[type=password]:focus,.login input[type=email]:focus{border-color:#9b2c33;box-shadow:0 0 0 1px #9b2c33;}',
		esc_url( saxon_login_logo_url() )
	);
	wp_register_style( 'saxon-login', false, array(), SAXON_VERSION );
	wp_enqueue_style( 'saxon-login' );
	wp_add_inline_style( 'saxon-login', $css );
}
add_action( 'login_enqueue_scripts', 'saxon_login_styles' );

/**
 * The logo links to the website, not WordPress.org.
 *
 * @return string
 */
function saxon_login_logo_link() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'saxon_login_logo_link' );

/**
 * Accessible name for the logo link.
 *
 * @return string
 */
function saxon_login_logo_text() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'saxon_login_logo_text' );
