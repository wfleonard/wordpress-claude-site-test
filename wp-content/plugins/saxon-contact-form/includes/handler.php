<?php
/**
 * Submission handler: nonce, spam checks, rate limit, wp_mail, storage.
 *
 * The form posts to admin-post.php. Scripted submissions add saxon_cf_ajax=1
 * and get JSON back; plain submissions are redirected back to the page.
 *
 * @package SaxonContactForm
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_saxon_contact', 'saxon_cf_handle_submission' );
add_action( 'admin_post_saxon_contact', 'saxon_cf_handle_submission' );

/**
 * Minimum seconds between page load and submit; faster is treated as a bot.
 */
const SAXON_CF_MIN_SECONDS = 3;

/**
 * Submissions allowed per visitor within SAXON_CF_RATE_WINDOW seconds.
 */
const SAXON_CF_RATE_LIMIT  = 5;
const SAXON_CF_RATE_WINDOW = 600;

/**
 * Failed (invalid) submissions allowed per visitor within the same window.
 * High enough that people fixing typos never hit it; it stops scripts from
 * filling the options table with saved form states.
 */
const SAXON_CF_FAIL_LIMIT = 20;

/**
 * Process a submission.
 */
function saxon_cf_handle_submission() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read only to choose the response format.
	$is_ajax = ! empty( $_POST['saxon_cf_ajax'] );

	if ( ! isset( $_POST['saxon_cf_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['saxon_cf_nonce'] ) ), 'saxon_contact' ) ) {
		saxon_cf_respond( $is_ajax, false, array(), array(), 'expired' );
	}

	$raw = wp_unslash( $_POST );

	if ( saxon_cf_is_spam( $raw ) ) {
		// Look successful so bots learn nothing, but send and store nothing.
		saxon_cf_respond( $is_ajax, true, array(), array() );
	}

	if ( saxon_cf_rate_limited() ) {
		saxon_cf_respond( $is_ajax, false, array(), array(), 'limited' );
	}

	$result = saxon_cf_validate( $raw );
	if ( $result['errors'] ) {
		saxon_cf_count_failure();
		saxon_cf_respond( $is_ajax, false, $result['errors'], $result['values'] );
	}

	saxon_cf_count_submission();

	$values  = $result['values'];
	$page_id = url_to_postid( (string) wp_get_referer() );
	$sent    = saxon_cf_send_mail( $values, $page_id );

	if ( saxon_cf_option( 'store' ) ) {
		saxon_cf_store_message( $values, $page_id, $sent );
	}

	/**
	 * Fires after a valid contact form submission.
	 *
	 * @param array $values  Sanitized field values.
	 * @param bool  $sent    Whether wp_mail reported success.
	 * @param int   $page_id Page the form was on, or 0.
	 */
	do_action( 'saxon_cf_submitted', $values, $sent, $page_id );

	if ( ! $sent && ! saxon_cf_option( 'store' ) ) {
		saxon_cf_respond( $is_ajax, false, array( '_form' => saxon_cf_form_error( 'failed' ) ), $values );
	}

	saxon_cf_respond( $is_ajax, true, array(), array() );
}

/**
 * Honeypot and timing checks.
 *
 * @param array $raw Unslashed request data.
 * @return bool
 */
function saxon_cf_is_spam( $raw ) {
	if ( ! empty( $raw['website'] ) ) {
		return true;
	}

	$token = isset( $raw['saxon_cf_ts'] ) && is_string( $raw['saxon_cf_ts'] ) ? $raw['saxon_cf_ts'] : '';
	$parts = explode( '.', $token, 2 );
	if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
		return true;
	}
	if ( ! hash_equals( substr( wp_hash( $parts[0] . '|saxon_cf' ), 0, 20 ), $parts[1] ) ) {
		return true;
	}

	return ( time() - (int) $parts[0] ) < SAXON_CF_MIN_SECONDS;
}

/**
 * Transient key for the visitor's submission count.
 *
 * The visitor is identified by a salted hash of their IP address; the
 * address itself is never stored.
 *
 * @return string
 */
function saxon_cf_rate_key( $kind = 'rate' ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return 'saxon_cf_' . $kind . '_' . substr( wp_hash( $ip . '|saxon_cf_rate' ), 0, 32 );
}

/**
 * Whether the visitor has used up their submissions, or failed attempts, for now.
 *
 * @return bool
 */
function saxon_cf_rate_limited() {
	return (int) get_transient( saxon_cf_rate_key() ) >= SAXON_CF_RATE_LIMIT
		|| (int) get_transient( saxon_cf_rate_key( 'fail' ) ) >= SAXON_CF_FAIL_LIMIT;
}

/**
 * Count a submission that failed validation.
 */
function saxon_cf_count_failure() {
	$key = saxon_cf_rate_key( 'fail' );
	set_transient( $key, (int) get_transient( $key ) + 1, SAXON_CF_RATE_WINDOW );
}

/**
 * Message for a form level error code.
 *
 * @param string $code expired, limited or failed.
 * @return string
 */
function saxon_cf_form_error( $code ) {
	switch ( $code ) {
		case 'expired':
			return __( 'This form has expired. Please reload the page and try again.', 'saxon-contact-form' );
		case 'limited':
			return __( 'You have sent several messages in a short time. Please wait a few minutes and try again.', 'saxon-contact-form' );
		case 'failed':
			return __( 'Sorry, your message could not be sent. Please try again, or contact us by phone or email.', 'saxon-contact-form' );
	}
	return __( 'Your message was not sent. Please try again.', 'saxon-contact-form' );
}

/**
 * Count a valid submission against the visitor's limit. Failed validation
 * does not count, so fixing a typo never locks anyone out.
 */
function saxon_cf_count_submission() {
	$key = saxon_cf_rate_key();
	set_transient( $key, (int) get_transient( $key ) + 1, SAXON_CF_RATE_WINDOW );
}

/**
 * Email the message to the site's recipients.
 *
 * @param array $values  Sanitized values.
 * @param int   $page_id Source page.
 * @return bool
 */
function saxon_cf_send_mail( $values, $page_id ) {
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	/* translators: %s: sender name. */
	$subject = trim( saxon_cf_option( 'subject_prefix' ) . ' ' . sprintf( __( 'New message from %s', 'saxon-contact-form' ), $values['name'] ) );

	$lines = array();
	foreach ( saxon_cf_fields() as $name => $field ) {
		if ( 'message' === $name || '' === ( $values[ $name ] ?? '' ) ) {
			continue;
		}
		$lines[] = $field['label'] . ': ' . $values[ $name ];
	}
	if ( $page_id ) {
		$lines[] = __( 'Page', 'saxon-contact-form' ) . ': ' . get_permalink( $page_id );
	}
	$lines[] = '';
	$lines[] = $values['message'];
	$lines[] = '';
	$lines[] = '--';
	/* translators: %s: site name. */
	$lines[] = sprintf( __( 'Sent from the contact form on %s. Reply to this email to answer the sender.', 'saxon-contact-form' ), $site );

	// Name and email are already free of line breaks (sanitize_text_field and
	// sanitize_email). wp_mail() also splits address headers on commas, so
	// strip address syntax from the display name to keep it a single address.
	$reply_name = trim( preg_replace( '/[",;<>\\\\[:cntrl:]]+/', ' ', $values['name'] ) );
	$headers    = array(
		'Content-Type: text/plain; charset=UTF-8',
		sprintf( 'Reply-To: "%s" <%s>', $reply_name, $values['email'] ),
	);

	/**
	 * Filter the email before it is sent.
	 *
	 * @param array $mail   { to, subject, message, headers }.
	 * @param array $values Sanitized values.
	 */
	$mail = apply_filters(
		'saxon_cf_mail',
		array(
			'to'      => saxon_cf_recipients(),
			'subject' => $subject,
			'message' => implode( "\n", $lines ),
			'headers' => $headers,
		),
		$values
	);

	return (bool) wp_mail( $mail['to'], $mail['subject'], $mail['message'], $mail['headers'] );
}

/**
 * Send the response and stop.
 *
 * @param bool  $is_ajax Scripted request.
 * @param bool  $success Outcome.
 * @param array $errors  Field errors keyed by name, "_form" for general.
 * @param array  $values  Values to refill the form with.
 * @param string $code    Form level error code (see saxon_cf_form_error()).
 *                        Sent in the URL, so nothing is stored for it.
 */
function saxon_cf_respond( $is_ajax, $success, $errors, $values, $code = '' ) {
	if ( '' !== $code ) {
		$errors = array( '_form' => saxon_cf_form_error( $code ) );
	}

	if ( $is_ajax ) {
		if ( $success ) {
			wp_send_json_success( array( 'message' => saxon_cf_option( 'success' ) ) );
		}
		wp_send_json_error( array( 'errors' => (object) $errors ), 400 );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- an element id, validated below.
	$anchor   = isset( $_POST['saxon_cf_return'] ) ? sanitize_html_class( wp_unslash( $_POST['saxon_cf_return'] ) ) : '';
	$referer  = wp_get_referer();
	$redirect = $referer ? remove_query_arg( array( 'saxon_cf', 'saxon_cf_key', 'saxon_cf_code' ), $referer ) : home_url( '/' );
	$redirect = preg_replace( '/#.*$/', '', $redirect );

	if ( $success ) {
		$redirect = add_query_arg( 'saxon_cf', 'sent', $redirect );
	} elseif ( '' !== $code ) {
		$redirect = add_query_arg(
			array(
				'saxon_cf'      => 'error',
				'saxon_cf_code' => $code,
			),
			$redirect
		);
	} else {
		$key = strtolower( wp_generate_password( 20, false ) );
		set_transient(
			'saxon_cf_state_' . $key,
			array(
				'values' => $values,
				'errors' => $errors,
			),
			10 * MINUTE_IN_SECONDS
		);
		$redirect = add_query_arg(
			array(
				'saxon_cf'     => 'error',
				'saxon_cf_key' => $key,
			),
			$redirect
		);
	}

	if ( $anchor ) {
		$redirect .= '#' . $anchor;
	}

	wp_safe_redirect( $redirect, 303 );
	exit;
}
