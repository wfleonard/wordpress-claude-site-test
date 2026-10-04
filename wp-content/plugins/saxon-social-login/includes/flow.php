<?php
/**
 * OpenID Connect sign in: start (wp-login.php?action=saxon_sso) and callback (REST route).
 *
 * Authorization code flow with PKCE, a one time state bound to the browser by a cookie,
 * and a nonce. The ID token comes straight from the provider's token endpoint over TLS,
 * so its claims are trusted without a signature check (OpenID Connect Core 3.1.3.7).
 *
 * @package SaxonSocialLogin
 * @author  William Leonard, Saxon Enterprises, Inc.
 */

defined( 'ABSPATH' ) || exit;

const SAXON_SSO_STATE_COOKIE = 'saxon_sso_state';
const SAXON_SSO_STATE_TTL    = 600;

/**
 * Start sign in or account linking, then send the browser to the provider.
 */
function saxon_sso_start() {
	$providers = saxon_sso_providers();
	$provider  = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! isset( $providers[ $provider ] ) ) {
		saxon_sso_fail( 'provider' );
	}

	$link_user = 0;
	if ( ! empty( $_GET['link'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! is_user_logged_in() || ! wp_verify_nonce( $nonce, 'saxon_sso_link_' . $provider ) ) {
			saxon_sso_fail( 'state' );
		}
		$link_user = get_current_user_id();
	}

	$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	$state    = saxon_sso_random();
	$verifier = saxon_sso_random() . saxon_sso_random();
	$nonce    = saxon_sso_random();

	set_transient(
		saxon_sso_state_key( $state ),
		array(
			'provider'    => $provider,
			'verifier'    => $verifier,
			'nonce'       => $nonce,
			'redirect_to' => $redirect_to,
			'link_user'   => $link_user,
		),
		SAXON_SSO_STATE_TTL
	);
	saxon_sso_set_state_cookie( $state, time() + SAXON_SSO_STATE_TTL );

	$args = array_merge(
		array(
			'client_id'             => $providers[ $provider ]['client_id'],
			'response_type'         => 'code',
			'redirect_uri'          => saxon_sso_callback_url(),
			'scope'                 => 'openid email profile',
			'state'                 => $state,
			'nonce'                 => $nonce,
			'code_challenge'        => saxon_sso_base64url( hash( 'sha256', $verifier, true ) ),
			'code_challenge_method' => 'S256',
		),
		$providers[ $provider ]['extra_params']
	);

	nocache_headers();
	wp_redirect( add_query_arg( rawurlencode_deep( $args ), $providers[ $provider ]['authorize_url'] ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- fixed provider URL.
	exit;
}
add_action( 'login_form_saxon_sso', 'saxon_sso_start' );

/**
 * Register the callback route that both providers redirect back to.
 */
function saxon_sso_register_route() {
	register_rest_route(
		'saxon-sso/v1',
		'/callback',
		array(
			'methods'             => 'GET',
			'callback'            => 'saxon_sso_callback',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'saxon_sso_register_route' );

/**
 * Handle the provider's redirect: check state, exchange the code, sign in or link.
 *
 * @param WP_REST_Request $request Request.
 */
function saxon_sso_callback( WP_REST_Request $request ) {
	nocache_headers();

	$state  = (string) $request->get_param( 'state' );
	$cookie = isset( $_COOKIE[ SAXON_SSO_STATE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ SAXON_SSO_STATE_COOKIE ] ) ) : '';
	saxon_sso_set_state_cookie( '', time() - YEAR_IN_SECONDS );

	if ( '' === $state || '' === $cookie || ! hash_equals( $cookie, $state ) ) {
		saxon_sso_fail( 'state' );
	}

	$saved = get_transient( saxon_sso_state_key( $state ) );
	delete_transient( saxon_sso_state_key( $state ) );
	if ( ! is_array( $saved ) ) {
		saxon_sso_fail( 'state' );
	}

	$link_user = (int) $saved['link_user'];
	$provider  = $saved['provider'];
	$providers = saxon_sso_providers();

	// The user canceled or the provider refused.
	if ( $request->get_param( 'error' ) || ! $request->get_param( 'code' ) || ! isset( $providers[ $provider ] ) ) {
		saxon_sso_fail( 'canceled', $link_user );
	}

	$claims = saxon_sso_exchange_code( $providers[ $provider ], (string) $request->get_param( 'code' ), $saved['verifier'] );
	if ( ! $claims || ! saxon_sso_claims_ok( $provider, $providers[ $provider ], $claims, $saved['nonce'] ) ) {
		saxon_sso_fail( 'token', $link_user );
	}
	$subject = (string) $claims['sub'];

	if ( $link_user ) {
		saxon_sso_finish_link( $link_user, $provider, $subject );
	}

	$user = saxon_sso_linked_user( $provider, $subject );

	// Google confirms email ownership, so a verified Google address may match an existing user once.
	// Accounts created from a Microsoft sign in are skipped: their email was never verified.
	if ( ! $user && 'google' === $provider && ! empty( $claims['email'] ) && true === ( $claims['email_verified'] ?? false ) ) {
		$by_email = get_user_by( 'email', (string) $claims['email'] );
		if ( $by_email && ! get_user_meta( $by_email->ID, saxon_sso_meta_key( $provider ), true ) && ! get_user_meta( $by_email->ID, '_saxon_sso_unverified_email', true ) ) {
			update_user_meta( $by_email->ID, saxon_sso_meta_key( $provider ), $subject );
			$user = $by_email;
		}
	}

	// When the site allows registration, new people get a Subscriber account.
	if ( ! $user ) {
		$user = saxon_sso_register_subscriber( $provider, $claims );
		if ( ! $user instanceof WP_User ) {
			saxon_sso_fail( $user );
		}
		update_user_meta( $user->ID, saxon_sso_meta_key( $provider ), $subject );
	}

	/** This filter is documented in wp-includes/user.php */
	$user = apply_filters( 'authenticate', $user, $user->user_login, '' );
	if ( ! $user instanceof WP_User ) {
		saxon_sso_fail( 'blocked' );
	}

	wp_set_auth_cookie( $user->ID, false, is_ssl() );
	wp_set_current_user( $user->ID );
	/** This action is documented in wp-includes/user.php */
	do_action( 'wp_login', $user->user_login, $user );

	$requested = $saved['redirect_to'] ? $saved['redirect_to'] : admin_url();
	/** This filter is documented in wp-login.php */
	$redirect = apply_filters( 'login_redirect', wp_validate_redirect( $requested, admin_url() ), $requested, $user );
	wp_safe_redirect( $redirect ? $redirect : admin_url() );
	exit;
}

/**
 * Create a Subscriber for a first time Google or Microsoft sign in, when
 * Settings, General, "Anyone can register" is on.
 *
 * Google only reports addresses it has verified. Microsoft does not guarantee that,
 * so those accounts are flagged and never matched to a later Google sign in by email.
 *
 * @param string $provider Provider slug.
 * @param array  $claims   ID token claims.
 * @return WP_User|string The new user, or an error code for saxon_sso_fail().
 */
function saxon_sso_register_subscriber( $provider, $claims ) {
	if ( ! get_option( 'users_can_register' ) ) {
		return 'nouser';
	}

	$email = '';
	if ( 'google' === $provider && true === ( $claims['email_verified'] ?? false ) ) {
		$email = (string) ( $claims['email'] ?? '' );
	} elseif ( 'microsoft' === $provider ) {
		$email = (string) ( $claims['email'] ?? ( $claims['preferred_username'] ?? '' ) );
	}
	$email = strtolower( trim( $email ) );

	if ( ! is_email( $email ) ) {
		return 'noemail';
	}
	if ( email_exists( $email ) ) {
		return 'exists';
	}

	$base  = sanitize_user( strstr( $email, '@', true ), true );
	$base  = '' === $base ? 'user' : $base;
	$login = $base;
	for ( $i = 2; username_exists( $login ); $i++ ) {
		$login = $base . $i;
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 32, true, true ),
			'display_name' => isset( $claims['name'] ) ? sanitize_text_field( $claims['name'] ) : $login,
			'first_name'   => isset( $claims['given_name'] ) ? sanitize_text_field( $claims['given_name'] ) : '',
			'last_name'    => isset( $claims['family_name'] ) ? sanitize_text_field( $claims['family_name'] ) : '',
			'role'         => 'subscriber',
		)
	);
	if ( is_wp_error( $user_id ) ) {
		error_log( 'Saxon Social Login: sign up failed: ' . $user_id->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		return 'token';
	}

	if ( 'microsoft' === $provider ) {
		update_user_meta( $user_id, '_saxon_sso_unverified_email', 1 );
	}
	/** This action is documented in wp-includes/user.php */
	do_action( 'register_new_user', $user_id );

	return get_user_by( 'id', $user_id );
}

/**
 * Link a provider account to the signed in user who started the flow.
 *
 * @param int    $user_id  User who started linking.
 * @param string $provider Provider slug.
 * @param string $subject  Provider account id.
 */
function saxon_sso_finish_link( $user_id, $provider, $subject ) {
	// The REST API ignores login cookies without a REST nonce, so check the cookie directly.
	if ( wp_validate_auth_cookie( '', 'logged_in' ) !== $user_id ) {
		saxon_sso_fail( 'state' );
	}

	$owner = saxon_sso_linked_user( $provider, $subject );
	if ( $owner && $owner->ID !== $user_id ) {
		saxon_sso_fail( 'taken', $user_id );
	}

	update_user_meta( $user_id, saxon_sso_meta_key( $provider ), $subject );
	wp_safe_redirect( add_query_arg( 'saxon_sso', 'linked', admin_url( 'profile.php' ) ) . '#saxon-sso' );
	exit;
}

/**
 * Exchange an authorization code for the ID token claims.
 *
 * @param array  $config   Provider settings.
 * @param string $code     Authorization code.
 * @param string $verifier PKCE code verifier.
 * @return array|null Claims, or null on any failure.
 */
function saxon_sso_exchange_code( $config, $code, $verifier ) {
	$response = wp_remote_post(
		$config['token_url'],
		array(
			'timeout' => 15,
			'headers' => array( 'Accept' => 'application/json' ),
			'body'    => array(
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => saxon_sso_callback_url(),
				'client_id'     => $config['client_id'],
				'client_secret' => $config['client_secret'],
				'code_verifier' => $verifier,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'Saxon Social Login: token request failed: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		return null;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== wp_remote_retrieve_response_code( $response ) || empty( $body['id_token'] ) ) {
		$detail = is_array( $body ) && isset( $body['error'] ) ? (string) $body['error'] : 'HTTP ' . wp_remote_retrieve_response_code( $response );
		error_log( 'Saxon Social Login: token request rejected: ' . $detail ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		return null;
	}

	$parts = explode( '.', (string) $body['id_token'] );
	if ( 3 !== count( $parts ) ) {
		return null;
	}
	$claims = json_decode( saxon_sso_base64url_decode( $parts[1] ), true );
	return is_array( $claims ) ? $claims : null;
}

/**
 * Validate ID token claims.
 *
 * @param string $provider Provider slug.
 * @param array  $config   Provider settings.
 * @param array  $claims   Claims.
 * @param string $nonce    Nonce sent with the request.
 * @return bool
 */
function saxon_sso_claims_ok( $provider, $config, $claims, $nonce ) {
	$aud = isset( $claims['aud'] ) ? (array) $claims['aud'] : array();
	$now = time();

	return in_array( $config['client_id'], $aud, true )
		&& saxon_sso_issuer_ok( $provider, $claims )
		&& isset( $claims['exp'] ) && (int) $claims['exp'] > $now - 60
		&& isset( $claims['iat'] ) && (int) $claims['iat'] < $now + 300
		&& isset( $claims['nonce'] ) && hash_equals( $nonce, (string) $claims['nonce'] )
		&& ! empty( $claims['sub'] ) && is_string( $claims['sub'] );
}

/**
 * Send the browser back with an error code.
 *
 * @param string $code      Error code shown by saxon_sso_login_errors().
 * @param int    $link_user When linking, return to the profile screen instead of the login screen.
 */
function saxon_sso_fail( $code, $link_user = 0 ) {
	$base = $link_user ? admin_url( 'profile.php' ) : wp_login_url();
	wp_safe_redirect( add_query_arg( 'saxon_sso_error', $code, $base ) . ( $link_user ? '#saxon-sso' : '' ) );
	exit;
}

/**
 * @param string $value Cookie value.
 * @param int    $expires Expiry timestamp.
 */
function saxon_sso_set_state_cookie( $value, $expires ) {
	setcookie(
		SAXON_SSO_STATE_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => '/',
			'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * @param string $state State value.
 * @return string
 */
function saxon_sso_state_key( $state ) {
	return 'saxon_sso_' . substr( hash( 'sha256', $state ), 0, 40 );
}

/** @return string 43 character URL safe random string. */
function saxon_sso_random() {
	return saxon_sso_base64url( random_bytes( 32 ) );
}

/**
 * @param string $data Binary data.
 * @return string
 */
function saxon_sso_base64url( $data ) {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/**
 * @param string $data Base64url text.
 * @return string
 */
function saxon_sso_base64url_decode( $data ) {
	return (string) base64_decode( strtr( $data, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $data ) % 4 ) % 4 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}
