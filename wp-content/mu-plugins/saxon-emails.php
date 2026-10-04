<?php
/**
 * Plugin Name: Saxon Emails
 * Description: Replaces the WordPress account emails (welcome, new user notice, password reset, password changed) with Saxon Enterprises branded HTML emails that include a plain text version.
 * Version:     1.0.0
 * Author:      William Leonard, Saxon Enterprises, Inc.
 *
 * @package Saxon_Emails
 * @author  William Leonard, Saxon Enterprises, Inc.
 */

defined( 'ABSPATH' ) || exit;

const SAXON_EMAIL_COMPANY = 'Saxon Enterprises';
const SAXON_EMAIL_COLOR   = '#9b2c33';

/**
 * Plain text versions of the HTML emails built in this request, keyed by a marker in the HTML.
 *
 * @var array<string, string>
 */
$GLOBALS['saxon_email_alt'] = array();

/**
 * Build a branded email.
 *
 * @param string     $heading    Heading.
 * @param string[]   $paragraphs Plain text paragraphs (escaped here).
 * @param array|null $button     Optional array( label, url ).
 * @param string[]   $after      Plain text paragraphs after the button.
 * @return string HTML.
 */
function saxon_email_build( $heading, $paragraphs, $button = null, $after = array() ) {
	$site    = wp_parse_url( home_url(), PHP_URL_HOST );
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	$marker  = 'saxon-' . wp_generate_password( 12, false );
	$p_style = 'margin:0 0 16px;font-size:16px;line-height:1.55;color:#1b2430;';

	$body = '';
	foreach ( $paragraphs as $text ) {
		$body .= '<p style="' . $p_style . '">' . nl2br( esc_html( $text ) ) . '</p>';
	}
	if ( $button ) {
		$body .= '<p style="margin:24px 0;"><a href="' . esc_url( $button[1] ) . '" style="display:inline-block;padding:12px 24px;border-radius:4px;background:' . SAXON_EMAIL_COLOR . ';color:#ffffff;font-weight:bold;font-size:16px;text-decoration:none;">' . esc_html( $button[0] ) . '</a></p>';
		$body .= '<p style="margin:0 0 16px;font-size:13px;line-height:1.5;color:#4a5565;">' . esc_html__( 'If the button does not work, copy this link into your browser:', 'saxon' ) . '<br><a href="' . esc_url( $button[1] ) . '" style="color:' . SAXON_EMAIL_COLOR . ';word-break:break-all;">' . esc_html( $button[1] ) . '</a></p>';
	}
	foreach ( $after as $text ) {
		$body .= '<p style="' . $p_style . '">' . nl2br( esc_html( $text ) ) . '</p>';
	}

	$brand = $logo
		? '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( SAXON_EMAIL_COMPANY ) . '" width="180" style="display:block;width:180px;max-width:100%;height:auto;border:0;">'
		: '<span style="font-family:Georgia,serif;font-size:24px;font-weight:bold;color:' . SAXON_EMAIL_COLOR . ';">' . esc_html( SAXON_EMAIL_COMPANY ) . '</span>';

	$html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . esc_html( $heading ) . '</title></head>'
		. '<body style="margin:0;padding:0;background:#f5f7fa;" data-saxon="' . esc_attr( $marker ) . '">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;"><tr><td align="center" style="padding:32px 16px;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:8px;border-top:4px solid ' . SAXON_EMAIL_COLOR . ';">'
		. '<tr><td style="padding:28px 32px 8px;">' . $brand . '</td></tr>'
		. '<tr><td style="padding:16px 32px 24px;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">'
		. '<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#1b2430;">' . esc_html( $heading ) . '</h1>' . $body
		. '<p style="margin:24px 0 0;font-size:16px;line-height:1.55;color:#1b2430;">' . esc_html__( 'Kind regards,', 'saxon' ) . '<br>' . esc_html( SAXON_EMAIL_COMPANY ) . '</p>'
		. '</td></tr></table>'
		. '<p style="max-width:560px;margin:16px auto 0;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;color:#6b7684;">'
		. esc_html( SAXON_EMAIL_COMPANY . ', Inc. · ' . $site ) . '</p>'
		. '</td></tr></table></body></html>';

	// Plain text version for mail apps that do not show HTML.
	$text = $heading . "\n\n" . implode( "\n\n", $paragraphs );
	if ( $button ) {
		$text .= "\n\n" . $button[0] . ":\n" . $button[1];
	}
	if ( $after ) {
		$text .= "\n\n" . implode( "\n\n", $after );
	}
	$text .= "\n\n" . __( 'Kind regards,', 'saxon' ) . "\n" . SAXON_EMAIL_COMPANY . "\n\n" . SAXON_EMAIL_COMPANY . ', Inc. · ' . $site;

	$GLOBALS['saxon_email_alt'][ $marker ] = $text;
	return $html;
}

/**
 * Add the plain text version to messages built by saxon_email_build().
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
 */
function saxon_email_alt_body( $phpmailer ) {
	if ( preg_match( '/data-saxon="(saxon-[A-Za-z0-9]+)"/', (string) $phpmailer->Body, $m ) && isset( $GLOBALS['saxon_email_alt'][ $m[1] ] ) ) {
		$phpmailer->AltBody = $GLOBALS['saxon_email_alt'][ $m[1] ];
	}
}
add_action( 'phpmailer_init', 'saxon_email_alt_body', 20 );

/**
 * @param array|string $headers Existing headers.
 * @return array
 */
function saxon_email_html_headers( $headers ) {
	$headers   = is_array( $headers ) ? $headers : array_filter( explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) ) );
	$headers   = array_filter( $headers, static fn( $h ) => 0 !== stripos( $h, 'content-type:' ) );
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	return array_values( $headers );
}

/**
 * Providers the user has connected with Saxon Social Login, if that plugin is active.
 *
 * @param int $user_id User id.
 * @return string[] Provider names.
 */
function saxon_email_connected_providers( $user_id ) {
	$names = array();
	foreach ( array( 'google' => 'Google', 'microsoft' => 'Microsoft' ) as $slug => $name ) {
		if ( get_user_meta( $user_id, '_saxon_sso_' . $slug, true ) ) {
			$names[] = $name;
		}
	}
	return $names;
}

/**
 * Welcome email to the new user.
 *
 * @param array   $email    to, subject, message, headers.
 * @param WP_User $user     New user.
 * @return array
 */
function saxon_email_welcome( $email, $user ) {
	$key = get_password_reset_key( $user );
	if ( is_wp_error( $key ) ) {
		return $email;
	}
	$set_url   = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' );
	$providers = saxon_email_connected_providers( $user->ID );
	$name      = $user->first_name ? $user->first_name : $user->display_name;

	$paragraphs = array(
		/* translators: %s: first name. */
		sprintf( __( 'Hello %s,', 'saxon' ), $name ),
		/* translators: %s: company name. */
		sprintf( __( 'Thank you for registering with %s. Your account is ready.', 'saxon' ), SAXON_EMAIL_COMPANY ),
		/* translators: %s: username. */
		sprintf( __( 'Your username is: %s', 'saxon' ), $user->user_login ),
	);

	if ( $providers ) {
		/* translators: %s: Google or Microsoft. */
		$paragraphs[] = sprintf( __( 'You signed up with your %s account, so you can sign in any time with the "Sign in with %s" button. You do not need a password, but you can create one below if you would also like to sign in with your username.', 'saxon' ), $providers[0], $providers[0] );
		$button       = array( __( 'Create a password (optional)', 'saxon' ), $set_url );
	} else {
		$paragraphs[] = __( 'To finish setting up your account, please create your password using the button below.', 'saxon' );
		$button       = array( __( 'Create your password', 'saxon' ), $set_url );
	}

	$after = array(
		__( 'For your security, this link can be used once and expires in 24 hours. If it expires, use "Lost your password?" on the sign in page to get a new one.', 'saxon' ),
		/* translators: %s: login URL. */
		sprintf( __( 'Sign in at: %s', 'saxon' ), wp_login_url() ),
		__( 'If you did not create this account, you can ignore this email or reply to let us know.', 'saxon' ),
	);

	return array(
		'to'      => $user->user_email,
		/* translators: %s: company name. */
		'subject' => sprintf( __( 'Welcome to %s', 'saxon' ), SAXON_EMAIL_COMPANY ),
		'message' => saxon_email_build( __( 'Welcome aboard', 'saxon' ), $paragraphs, $button, $after ),
		'headers' => saxon_email_html_headers( $email['headers'] ?? array() ),
	);
}
add_filter( 'wp_new_user_notification_email', 'saxon_email_welcome', 10, 2 );

/**
 * New user notice to the site administrator.
 *
 * @param array   $email to, subject, message, headers.
 * @param WP_User $user  New user.
 * @return array
 */
function saxon_email_admin_notice( $email, $user ) {
	$providers = saxon_email_connected_providers( $user->ID );
	$method    = $providers ? $providers[0] : __( 'email and password', 'saxon' );

	$paragraphs = array(
		__( 'A new user has registered on the website.', 'saxon' ),
		/* translators: 1: display name, 2: username, 3: email, 4: sign up method, 5: role. */
		sprintf( __( "Name: %1\$s\nUsername: %2\$s\nEmail: %3\$s\nSigned up with: %4\$s\nRole: %5\$s", 'saxon' ), $user->display_name, $user->user_login, $user->user_email, $method, implode( ', ', $user->roles ) ),
	);

	return array(
		'to'      => $email['to'],
		/* translators: %s: user display name. */
		'subject' => sprintf( __( 'New website registration: %s', 'saxon' ), $user->display_name ),
		'message' => saxon_email_build( __( 'New user registration', 'saxon' ), $paragraphs, array( __( 'View user', 'saxon' ), admin_url( 'user-edit.php?user_id=' . $user->ID ) ) ),
		'headers' => saxon_email_html_headers( $email['headers'] ?? array() ),
	);
}
add_filter( 'wp_new_user_notification_email_admin', 'saxon_email_admin_notice', 10, 2 );

/**
 * Password reset email.
 *
 * @param array   $email      to, subject, message, headers.
 * @param string  $key        Reset key.
 * @param string  $user_login Username.
 * @param WP_User $user       User.
 * @return array
 */
function saxon_email_password_reset( $email, $key, $user_login, $user ) {
	$url  = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user_login ), 'login' );
	$name = $user->first_name ? $user->first_name : $user->display_name;

	$paragraphs = array(
		/* translators: %s: first name. */
		sprintf( __( 'Hello %s,', 'saxon' ), $name ),
		/* translators: 1: company name, 2: username. */
		sprintf( __( 'We received a request to reset the password for your %1$s account (username: %2$s).', 'saxon' ), SAXON_EMAIL_COMPANY, $user_login ),
	);
	$after = array(
		__( 'This link can be used once and expires in 24 hours.', 'saxon' ),
		__( 'If you did not ask for this, you can ignore this email and your password will stay the same.', 'saxon' ),
	);

	$email['subject'] = sprintf(
		/* translators: %s: company name. */
		__( 'Reset your %s password', 'saxon' ),
		SAXON_EMAIL_COMPANY
	);
	$email['message'] = saxon_email_build( __( 'Password reset request', 'saxon' ), $paragraphs, array( __( 'Reset your password', 'saxon' ), $url ), $after );
	$email['headers'] = saxon_email_html_headers( $email['headers'] ?? array() );
	return $email;
}
add_filter( 'retrieve_password_notification_email', 'saxon_email_password_reset', 10, 4 );

/**
 * "Your password was changed" email to the user.
 *
 * @param array $email to, subject, message, headers.
 * @param array $user  User data before the change.
 * @return array
 */
function saxon_email_password_changed( $email, $user ) {
	$name = ! empty( $user['first_name'] ) ? $user['first_name'] : $user['display_name'];

	$paragraphs = array(
		/* translators: %s: first name. */
		sprintf( __( 'Hello %s,', 'saxon' ), $name ),
		/* translators: %s: company name. */
		sprintf( __( 'This is a confirmation that the password for your %s account was just changed.', 'saxon' ), SAXON_EMAIL_COMPANY ),
	);
	$after = array(
		/* translators: %s: admin email. */
		sprintf( __( 'If you did not make this change, please reset your password right away and contact us at %s.', 'saxon' ), get_option( 'admin_email' ) ),
	);

	$email['subject'] = sprintf(
		/* translators: %s: company name. */
		__( 'Your %s password was changed', 'saxon' ),
		SAXON_EMAIL_COMPANY
	);
	$email['message'] = saxon_email_build( __( 'Password changed', 'saxon' ), $paragraphs, array( __( 'Sign in', 'saxon' ), wp_login_url() ), $after );
	$email['headers'] = saxon_email_html_headers( $email['headers'] ?? array() );
	return $email;
}
add_filter( 'password_change_email', 'saxon_email_password_changed', 10, 2 );

/**
 * Notice to the administrator when a user resets their password.
 *
 * @param array   $email    to, subject, message, headers.
 * @param WP_User $user     User.
 * @return array
 */
function saxon_email_admin_password_notice( $email, $user ) {
	$paragraphs = array(
		/* translators: 1: display name, 2: username. */
		sprintf( __( '%1$s (username: %2$s) has set a new password on the website.', 'saxon' ), $user->display_name, $user->user_login ),
	);

	/* translators: %s: user display name. */
	$email['subject'] = sprintf( __( 'Password set by %s', 'saxon' ), $user->display_name );
	$email['message'] = saxon_email_build( __( 'Password updated', 'saxon' ), $paragraphs, array( __( 'View user', 'saxon' ), admin_url( 'user-edit.php?user_id=' . $user->ID ) ) );
	$email['headers'] = saxon_email_html_headers( $email['headers'] ?? array() );
	return $email;
}
add_filter( 'wp_password_change_notification_email', 'saxon_email_admin_password_notice', 10, 2 );
