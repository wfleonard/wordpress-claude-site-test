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
 *
 * @param bool $sign_up Label the buttons for the registration screen.
 */
function saxon_sso_login_buttons( $sign_up = false ) {
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
			esc_html(
				$sign_up
					/* translators: %s: Google or Microsoft. */
					? sprintf( __( 'Sign up with %s', 'saxon-social-login' ), $provider['label'] )
					/* translators: %s: Google or Microsoft. */
					: sprintf( __( 'Sign in with %s', 'saxon-social-login' ), $provider['label'] )
			)
		);
	}
	echo '</div>';
}
add_action( 'login_footer', 'saxon_sso_login_buttons_footer', 1 );

/**
 * Buttons go after the form on the login screen, and on the registration screen
 * when the site allows registration (not on lost password).
 */
function saxon_sso_login_buttons_footer() {
	global $action;
	$screen = isset( $action ) ? $action : 'login';
	if ( 'register' === $screen && get_option( 'users_can_register' ) ) {
		$form = 'registerform';
	} elseif ( 'login' === $screen ) {
		$form = 'loginform';
	} else {
		return;
	}
	ob_start();
	saxon_sso_login_buttons( 'registerform' === $form );
	$html = ob_get_clean();
	if ( $html ) {
		// Moved into #login, right after the form, by a tiny inline script; plain links work without it.
		echo '<template id="saxon-sso-template">' . $html . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built above.
		echo '<script>(function(){var t=document.getElementById("saxon-sso-template"),f=document.getElementById(' . wp_json_encode( $form ) . ');if(t&&f){f.parentNode.insertBefore(t.content.cloneNode(true),f.nextSibling);}})();</script>';
		echo '<noscript>' . $html . '</noscript>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built above.
	}
}

/**
 * Styles for the buttons and the hidden spam field.
 */
function saxon_sso_login_styles() {
	wp_enqueue_style( 'saxon-sso-login', SAXON_SSO_URL . 'assets/css/login.css', array(), SAXON_SSO_VERSION );
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
		'exists'   => __( 'An account with that email address already exists. Sign in with your username and password, then connect this account from your profile.', 'saxon-social-login' ),
		'noemail'  => __( 'That account did not share an email address, so we could not create an account. Please register with your email address instead.', 'saxon-social-login' ),
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

/**
 * Spam check on the email and password registration form: a hidden field people
 * never fill in, and a minimum of three seconds between showing and sending the form.
 */
function saxon_sso_register_trap() {
	printf(
		'<p class="saxon-sso__trap" aria-hidden="true"><label>%1$s <input type="text" name="saxon_sso_website" value="" tabindex="-1" autocomplete="off"></label></p><input type="hidden" name="saxon_sso_ts" value="%2$s">',
		esc_html__( 'Leave this empty', 'saxon-social-login' ),
		esc_attr( time() . '.' . wp_hash( 'saxon_sso_ts' . time() ) )
	);
}
add_action( 'register_form', 'saxon_sso_register_trap' );

/**
 * @param WP_Error $errors Registration errors.
 * @return WP_Error
 */
function saxon_sso_register_trap_check( $errors ) {
	$filled = ! empty( $_POST['saxon_sso_website'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$parts  = explode( '.', isset( $_POST['saxon_sso_ts'] ) ? sanitize_text_field( wp_unslash( $_POST['saxon_sso_ts'] ) ) : '', 2 ); // phpcs:ignore WordPress.Security.NonceVerification
	$time   = (int) $parts[0];
	$valid  = 2 === count( $parts ) && hash_equals( wp_hash( 'saxon_sso_ts' . $time ), $parts[1] );
	if ( $filled || ! $valid || time() - $time < 3 || time() - $time > DAY_IN_SECONDS ) {
		$errors->add( 'saxon_sso_spam', __( 'Sorry, we could not process that registration. Please wait a moment and try again.', 'saxon-social-login' ) );
	}
	return $errors;
}
add_filter( 'registration_errors', 'saxon_sso_register_trap_check' );
