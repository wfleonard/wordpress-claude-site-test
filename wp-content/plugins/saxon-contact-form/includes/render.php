<?php
/**
 * Form rendering for the block and the shortcode.
 *
 * @package SaxonContactForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode callback: [saxon_contact_form].
 *
 * @return string
 */
function saxon_cf_shortcode() {
	return saxon_cf_render_form();
}

/**
 * Signed timestamp for the "submitted too fast" spam check.
 *
 * @return string
 */
function saxon_cf_time_token() {
	$time = time();
	return $time . '.' . substr( wp_hash( $time . '|saxon_cf' ), 0, 20 );
}

/**
 * Read the result of a submission made without JavaScript (post, redirect, get).
 *
 * @return array { status: string, values: array, errors: array }
 */
function saxon_cf_request_state() {
	$state = array(
		'status' => '',
		'values' => array(),
		'errors' => array(),
	);

	// Read only display state; the form data itself was verified by the handler.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$status = isset( $_GET['saxon_cf'] ) ? sanitize_key( wp_unslash( $_GET['saxon_cf'] ) ) : '';
	$key    = isset( $_GET['saxon_cf_key'] ) ? sanitize_key( wp_unslash( $_GET['saxon_cf_key'] ) ) : '';
	// phpcs:enable

	if ( 'sent' === $status ) {
		$state['status'] = 'sent';
	} elseif ( 'error' === $status && $key ) {
		$saved = get_transient( 'saxon_cf_state_' . $key );
		if ( is_array( $saved ) ) {
			$state = array_merge( $state, $saved, array( 'status' => 'error' ) );
		} else {
			$state['status'] = 'error';
			$state['errors'] = array( '_form' => __( 'Your message was not sent. Please try again.', 'saxon-contact-form' ) );
		}
	}

	return $state;
}

/**
 * Render the form.
 *
 * @return string
 */
function saxon_cf_render_form() {
	static $instance = 0;
	++$instance;

	wp_enqueue_style( 'saxon-cf' );
	wp_enqueue_script( 'saxon-cf' );

	$state   = saxon_cf_request_state();
	$id      = 'saxon-cf-' . $instance;
	$consent = (string) saxon_cf_option( 'consent' );

	ob_start();
	include SAXON_CF_DIR . 'templates/form.php';
	return (string) ob_get_clean();
}

/**
 * Consent label with a privacy policy link when the site has one.
 *
 * @param string $text Consent text.
 * @return string Safe HTML.
 */
function saxon_cf_consent_label( $text ) {
	$label = esc_html( $text );
	$url   = get_privacy_policy_url();
	if ( $url ) {
		$label .= ' ' . sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html__( 'Read our privacy policy.', 'saxon-contact-form' ) );
	}
	return $label;
}
