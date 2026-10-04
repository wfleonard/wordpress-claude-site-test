<?php
/**
 * Sign in buttons and error messages on wp-login.php.
 *
 * @package SaxonSocialLogin
 * @author  William Leonard, Saxon Enterprises, Inc.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Start URL for a provider.
 *
 * @param string $provider    Provider slug.
 * @param string $redirect_to Where to go after sign in.
 * @return string
 */
function saxon_sso_start_url( $provider, $redirect_to = '' ) {
	$args = array(
		'action'   => 'saxon_sso',
		'provider' => $provider,
	);
	if ( $redirect_to ) {
		$args['redirect_to'] = $redirect_to;
	}
	return add_query_arg( rawurlencode_deep( $args ), wp_login_url() );
}

/**
 * Small provider logo.
 *
 * @param string $provider Provider slug.
 * @return string SVG markup.
 */
function saxon_sso_logo( $provider ) {
	if ( 'google' === $provider ) {
		return '<svg class="saxon-sso__logo" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.6 5.4 2.7 13.3l7.9 6.1C12.5 13.6 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.6 6.9l7.5 5.8c4.4-4 6.8-10 6.8-17.2z"/><path fill="#FBBC05" d="M10.5 28.6A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.6l-7.9-6.1A24 24 0 0 0 0 24c0 3.9.9 7.5 2.6 10.7l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.5-5.8c-2.1 1.4-4.8 2.3-8.4 2.3-6.2 0-11.5-4.2-13.4-9.8l-7.9 6.1C6.6 42.6 14.6 48 24 48z"/></svg>';
	}
	return '<svg class="saxon-sso__logo" viewBox="0 0 21 21" aria-hidden="true" focusable="false"><path fill="#F25022" d="M1 1h9v9H1z"/><path fill="#7FBA00" d="M11 1h9v9h-9z"/><path fill="#00A4EF" d="M1 11h9v9H1z"/><path fill="#FFB900" d="M11 11h9v9h-9z"/></svg>';
}

/**
 * Print the buttons below the username and password form.
 */
function saxon_sso_login_buttons() {
	$providers = saxon_sso_providers();
	if ( ! $providers || ! empty( $_REQUEST['interim-login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	echo '<div class="saxon-sso"><p class="saxon-sso__or"><span>' . esc_html__( 'or', 'saxon-social-login' ) . '</span></p>';
	foreach ( $providers as $slug => $provider ) {
		printf(
			'<a class="saxon-sso__button" href="%1$s">%2$s<span>%3$s</span></a>',
			esc_url( saxon_sso_start_url( $slug, $redirect_to ) ),
			saxon_sso_logo( $slug ), // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG.
			/* translators: %s: Google or Microsoft. */
			esc_html( sprintf( __( 'Sign in with %s', 'saxon-social-login' ), $provider['label'] ) )
		);
	}
	echo '</div>';
}
add_action( 'login_footer', 'saxon_sso_login_buttons_footer', 1 );

/**
 * Buttons go after the form on the login screen only (not lost password or register).
 */
function saxon_sso_login_buttons_footer() {
	global $action;
	if ( isset( $action ) && 'login' !== $action ) {
		return;
	}
	ob_start();
	saxon_sso_login_buttons();
	$html = ob_get_clean();
	if ( $html ) {
		// Moved into #login, right after the form, by a tiny inline script; plain links work without it.
		echo '<template id="saxon-sso-template">' . $html . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built above.
		echo '<script>(function(){var t=document.getElementById("saxon-sso-template"),f=document.getElementById("loginform");if(t&&f){f.parentNode.insertBefore(t.content.cloneNode(true),f.nextSibling);}})();</script>';
		echo '<noscript>' . $html . '</noscript>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built above.
	}
}

/**
 * Styles for the buttons.
 */
function saxon_sso_login_styles() {
	if ( saxon_sso_providers() ) {
		wp_enqueue_style( 'saxon-sso-login', SAXON_SSO_URL . 'assets/css/login.css', array(), SAXON_SSO_VERSION );
	}
}
add_action( 'login_enqueue_scripts', 'saxon_sso_login_styles' );

/**
 * Error and status messages.
 *
 * @param string $code Error code.
 * @return string
 */
function saxon_sso_message( $code ) {
	$messages = array(
		'provider' => __( 'That sign in option is not available.', 'saxon-social-login' ),
		'state'    => __( 'Your sign in session expired. Please try again.', 'saxon-social-login' ),
		'canceled' => __( 'Sign in was canceled.', 'saxon-social-login' ),
		'token'    => __( 'We could not confirm your account with the provider. Please try again.', 'saxon-social-login' ),
		'nouser'   => __( 'No user on this site is connected to that account. Sign in with your username and password, then connect the account from your profile.', 'saxon-social-login' ),
		'blocked'  => __( 'Sign in is not allowed for this account.', 'saxon-social-login' ),
		'taken'    => __( 'That account is already connected to another user.', 'saxon-social-login' ),
	);
	return $messages[ $code ] ?? $messages['token'];
}

/**
 * Show errors on the login screen.
 *
 * @param WP_Error $errors Login errors.
 * @return WP_Error
 */
function saxon_sso_login_errors( $errors ) {
	if ( isset( $_GET['saxon_sso_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$errors->add( 'saxon_sso', esc_html( saxon_sso_message( sanitize_key( wp_unslash( $_GET['saxon_sso_error'] ) ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	return $errors;
}
add_filter( 'wp_login_errors', 'saxon_sso_login_errors' );
