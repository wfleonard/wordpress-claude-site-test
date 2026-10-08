<?php
/**
 * Plugin Name: Saxon SMTP
 * Description: Sends all WordPress email through Google Workspace. Uses the SMTP relay (smtp-relay.gmail.com, allowed by server IP, no password) unless an App Password is set. Does nothing until SAXON_SMTP_FROM is defined in wp-config.php.
 * Version:     1.1.1
 * Author:      William Leonard, Saxon Enterprises, Inc.
 *
 * wp-config.php (above "That's all, stop editing!"):
 *   define( 'SAXON_SMTP_FROM', 'you@yourdomain.com' );   // address in your Workspace domain that mail is sent from
 *   define( 'SAXON_SMTP_FROM_NAME', 'Saxon' );           // optional
 *   define( 'SAXON_SMTP_PASS', 'xxxx xxxx xxxx xxxx' );  // optional: Google App Password, switches to smtp.gmail.com with login
 *
 * @package Saxon_SMTP
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SAXON_SMTP_FROM' ) || ! is_email( SAXON_SMTP_FROM ) ) {
	return;
}

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) {
		$phpmailer->isSMTP();
		$phpmailer->Port       = 587;
		$phpmailer->SMTPSecure = 'tls';
		$phpmailer->Timeout    = 15;
		// Google's relay checks the HELO name, so announce the site's own domain.
		$phpmailer->Helo = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( defined( 'SAXON_SMTP_PASS' ) && '' !== SAXON_SMTP_PASS ) {
			$phpmailer->Host     = 'smtp.gmail.com';
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = SAXON_SMTP_FROM;
			$phpmailer->Password = str_replace( ' ', '', SAXON_SMTP_PASS );
		} else {
			// Relay mode: Google accepts the mail because it comes from this server's allowed IP.
			// Connect over IPv4 (the address allowed in Google Admin); the server would otherwise
			// prefer IPv6. TLS is still verified against the real host name.
			$relay               = 'smtp-relay.gmail.com';
			$ipv4                = gethostbyname( $relay );
			$phpmailer->Host     = $ipv4 !== $relay ? $ipv4 : $relay;
			$phpmailer->SMTPAuth = false;
			$phpmailer->SMTPOptions = array( 'ssl' => array( 'peer_name' => $relay ) );
		}

		// Send as a Workspace address so Google accepts and signs it.
		// Reply-To headers (the contact form sets the visitor's address) are left untouched.
		$name = defined( 'SAXON_SMTP_FROM_NAME' ) ? SAXON_SMTP_FROM_NAME : get_bloginfo( 'name' );
		$phpmailer->setFrom( SAXON_SMTP_FROM, $name, false );
	}
);

// Log delivery failures to the PHP error log so they are not silent.
add_action(
	'wp_mail_failed',
	static function ( $error ) {
		error_log( 'Saxon SMTP: ' . $error->get_error_message() );
	}
);
