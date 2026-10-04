<?php
/**
 * Provider settings (Google and Microsoft) read from wp-config.php constants.
 *
 * @package SaxonSocialLogin
 * @author  William Leonard, Saxon Enterprises, Inc.
 */

defined( 'ABSPATH' ) || exit;

/** Microsoft's tenant id for personal (outlook.com, hotmail.com) accounts. */
const SAXON_SSO_MS_CONSUMERS_TENANT = '9188040d-6c67-4c5b-b112-36a304b66dad';

/**
 * Providers that have both a client id and a client secret configured.
 *
 * @return array<string, array> Keyed by provider slug.
 */
function saxon_sso_providers() {
	$providers = array();

	if ( defined( 'SAXON_SSO_GOOGLE_CLIENT_ID' ) && defined( 'SAXON_SSO_GOOGLE_CLIENT_SECRET' ) && SAXON_SSO_GOOGLE_CLIENT_ID && SAXON_SSO_GOOGLE_CLIENT_SECRET ) {
		$providers['google'] = array(
			'label'         => __( 'Google', 'saxon-social-login' ),
			'client_id'     => SAXON_SSO_GOOGLE_CLIENT_ID,
			'client_secret' => SAXON_SSO_GOOGLE_CLIENT_SECRET,
			'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
			'token_url'     => 'https://oauth2.googleapis.com/token',
			'extra_params'  => array( 'prompt' => 'select_account' ),
		);
	}

	if ( defined( 'SAXON_SSO_MICROSOFT_CLIENT_ID' ) && defined( 'SAXON_SSO_MICROSOFT_CLIENT_SECRET' ) && SAXON_SSO_MICROSOFT_CLIENT_ID && SAXON_SSO_MICROSOFT_CLIENT_SECRET ) {
		$tenant                 = saxon_sso_microsoft_tenant();
		$providers['microsoft'] = array(
			'label'         => __( 'Microsoft', 'saxon-social-login' ),
			'client_id'     => SAXON_SSO_MICROSOFT_CLIENT_ID,
			'client_secret' => SAXON_SSO_MICROSOFT_CLIENT_SECRET,
			'authorize_url' => 'https://login.microsoftonline.com/' . rawurlencode( $tenant ) . '/oauth2/v2.0/authorize',
			'token_url'     => 'https://login.microsoftonline.com/' . rawurlencode( $tenant ) . '/oauth2/v2.0/token',
			'extra_params'  => array( 'prompt' => 'select_account' ),
		);
	}

	return $providers;
}

/**
 * Microsoft tenant: "common" (default, work and personal accounts),
 * "organizations", "consumers", or a specific tenant id.
 *
 * @return string
 */
function saxon_sso_microsoft_tenant() {
	$tenant = defined( 'SAXON_SSO_MICROSOFT_TENANT' ) ? strtolower( (string) SAXON_SSO_MICROSOFT_TENANT ) : 'common';
	if ( in_array( $tenant, array( 'common', 'organizations', 'consumers' ), true ) || saxon_sso_is_guid( $tenant ) ) {
		return $tenant;
	}
	return 'common';
}

/**
 * @param string $value Value to test.
 * @return bool
 */
function saxon_sso_is_guid( $value ) {
	return (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $value );
}

/**
 * The single redirect URI registered with both providers.
 *
 * @return string
 */
function saxon_sso_callback_url() {
	return rest_url( 'saxon-sso/v1/callback' );
}

/**
 * Check the issuer of an ID token for a provider.
 *
 * @param string $provider Provider slug.
 * @param array  $claims   ID token claims.
 * @return bool
 */
function saxon_sso_issuer_ok( $provider, $claims ) {
	$iss = isset( $claims['iss'] ) ? (string) $claims['iss'] : '';

	if ( 'google' === $provider ) {
		return in_array( $iss, array( 'https://accounts.google.com', 'accounts.google.com' ), true );
	}

	if ( 'microsoft' === $provider ) {
		$tid = isset( $claims['tid'] ) ? strtolower( (string) $claims['tid'] ) : '';
		if ( ! saxon_sso_is_guid( $tid ) || 'https://login.microsoftonline.com/' . $tid . '/v2.0' !== strtolower( $iss ) ) {
			return false;
		}
		$tenant = saxon_sso_microsoft_tenant();
		if ( 'consumers' === $tenant ) {
			return SAXON_SSO_MS_CONSUMERS_TENANT === $tid;
		}
		if ( 'organizations' === $tenant ) {
			return SAXON_SSO_MS_CONSUMERS_TENANT !== $tid;
		}
		if ( saxon_sso_is_guid( $tenant ) ) {
			return $tenant === $tid;
		}
		return true;
	}

	return false;
}

/**
 * User meta key that stores a user's account id at a provider.
 *
 * @param string $provider Provider slug.
 * @return string
 */
function saxon_sso_meta_key( $provider ) {
	return '_saxon_sso_' . $provider;
}

/**
 * Find the user linked to a provider account.
 *
 * @param string $provider Provider slug.
 * @param string $subject  Provider account id.
 * @return WP_User|null
 */
function saxon_sso_linked_user( $provider, $subject ) {
	$users = get_users(
		array(
			'meta_key'   => saxon_sso_meta_key( $provider ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => $subject, // phpcs:ignore WordPress.DB.SlowDBQuery
			'number'     => 2,
		)
	);
	return 1 === count( $users ) ? $users[0] : null;
}
