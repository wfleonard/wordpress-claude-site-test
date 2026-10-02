<?php
/**
 * Plugin Name:       Saxon Contact Form
 * Description:       Accessible contact form with nonce checks, a spam honeypot, rate limiting and email delivery through wp_mail. Add it with the Contact form block or the [saxon_contact_form] shortcode.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            William Leonard, CTI Global
 * Author URI:        mailto:bill.leonard@cticorp.com
 * License:           GPL-2.0-or-later
 * Text Domain:       saxon-contact-form
 *
 * @package SaxonContactForm
 * @author  William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

defined( 'ABSPATH' ) || exit;

define( 'SAXON_CF_VERSION', '1.0.0' );
define( 'SAXON_CF_FILE', __FILE__ );
define( 'SAXON_CF_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAXON_CF_URL', plugin_dir_url( __FILE__ ) );

require SAXON_CF_DIR . 'includes/settings.php';
require SAXON_CF_DIR . 'includes/fields.php';
require SAXON_CF_DIR . 'includes/render.php';
require SAXON_CF_DIR . 'includes/handler.php';
require SAXON_CF_DIR . 'includes/messages.php';

/**
 * Register front end assets, the block and the shortcode.
 */
function saxon_cf_init() {
	load_plugin_textdomain( 'saxon-contact-form', false, dirname( plugin_basename( SAXON_CF_FILE ) ) . '/languages' );

	wp_register_style( 'saxon-cf', SAXON_CF_URL . 'assets/css/contact-form.css', array(), saxon_cf_asset_version( 'assets/css/contact-form.css' ) );
	wp_register_script(
		'saxon-cf',
		SAXON_CF_URL . 'assets/js/contact-form.js',
		array(),
		saxon_cf_asset_version( 'assets/js/contact-form.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script(
		'saxon-cf',
		'saxonContactForm',
		array(
			'sending'   => __( 'Sending your message…', 'saxon-contact-form' ),
			'failed'    => __( 'Sorry, your message could not be sent. Please try again, or contact us by phone or email.', 'saxon-contact-form' ),
			'fixErrors' => __( 'Please fix the highlighted fields and try again.', 'saxon-contact-form' ),
			'required'  => __( 'This field is required.', 'saxon-contact-form' ),
			'email'     => __( 'Enter a valid email address, like name@example.com.', 'saxon-contact-form' ),
			'tooLong'   => __( 'This is too long.', 'saxon-contact-form' ),
		)
	);

	wp_register_script(
		'saxon-cf-block-editor',
		SAXON_CF_URL . 'blocks/contact-form/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		saxon_cf_asset_version( 'blocks/contact-form/editor.js' ),
		true
	);

	register_block_type( SAXON_CF_DIR . 'blocks/contact-form' );
	add_shortcode( 'saxon_contact_form', 'saxon_cf_shortcode' );
}
add_action( 'init', 'saxon_cf_init' );

/**
 * Load the assets in the head when the current page uses the shortcode or
 * the block, so the form is styled on first paint. Without this, themes that
 * load block styles on demand would print the stylesheet in the body.
 */
function saxon_cf_maybe_enqueue() {
	if ( ! is_singular() ) {
		return;
	}
	$content = (string) get_post_field( 'post_content', get_queried_object_id() );
	if ( has_shortcode( $content, 'saxon_contact_form' ) || has_block( 'saxon/contact-form', $content ) ) {
		wp_enqueue_style( 'saxon-cf' );
		wp_enqueue_script( 'saxon-cf' );
	}
}
add_action( 'wp_enqueue_scripts', 'saxon_cf_maybe_enqueue' );

/**
 * Cache busting version based on file modification time.
 *
 * @param string $relative_path Path relative to the plugin root.
 * @return string
 */
function saxon_cf_asset_version( $relative_path ) {
	$file = SAXON_CF_DIR . $relative_path;
	return file_exists( $file ) ? SAXON_CF_VERSION . '.' . filemtime( $file ) : SAXON_CF_VERSION;
}

/**
 * Settings link on the Plugins screen.
 *
 * @param string[] $links Action links.
 * @return string[]
 */
function saxon_cf_action_links( $links ) {
	array_unshift(
		$links,
		sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=saxon-contact-form' ) ), esc_html__( 'Settings', 'saxon-contact-form' ) )
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( SAXON_CF_FILE ), 'saxon_cf_action_links' );
