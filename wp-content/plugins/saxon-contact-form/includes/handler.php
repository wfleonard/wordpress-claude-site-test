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

	$raw   = wp_unslash( $_POST );
	$token = isset( $raw['saxon_cf_ts'] ) && is_string( $raw['saxon_cf_ts'] ) ? $raw['saxon_cf_ts'] : '';

	$reason = saxon_cf_is_spam( $raw );
	if ( $reason ) {
		// Look successful so bots learn nothing, but send and store nothing.
		saxon_cf_record_blocked( $reason );
		saxon_cf_respond( $is_ajax, true, array(), array() );
	}

	if ( saxon_cf_token_expired( $token ) ) {
		saxon_cf_respond( $is_ajax, false, array(), array(), 'expired' );
	}

	if ( saxon_cf_rate_limited() ) {
		saxon_cf_respond( $is_ajax, false, array(), array(), 'limited' );
	}

	$result = saxon_cf_validate( $raw );
	if ( ! $result['errors'] ) {
		$problem = saxon_cf_email_problem( $result['values']['email'] );
		if ( $problem ) {
			$result['errors']['email'] = $problem;
		}
	}
	if ( $result['errors'] ) {
		saxon_cf_count_failure();
		saxon_cf_respond( $is_ajax, false, $result['errors'], $result['values'] );
	}

	$values  = $result['values'];
	$page_id = url_to_postid( (string) wp_get_referer() );

	// Each loaded form can be sent once; a replayed form is a script.
	if ( ! saxon_cf_use_token( $token ) ) {
		saxon_cf_record_blocked( 'replay' );
		saxon_cf_respond( $is_ajax, true, array(), array() );
	}

	if ( saxon_cf_counter_get( 'e' . saxon_cf_hash( $values['email'] ), HOUR_IN_SECONDS ) >= SAXON_CF_EMAIL_LIMIT ) {
		saxon_cf_respond( $is_ajax, false, array(), array(), 'limited' );
	}

	// Content that reads like spam is not emailed. With "Keep a copy" on it is
	// saved as a pending message marked as spam, so nothing genuine is lost.
	$content_reason = saxon_cf_content_spam( $values );
	if ( $content_reason ) {
		saxon_cf_record_blocked( $content_reason );
		if ( saxon_cf_option( 'store' ) ) {
			saxon_cf_store_message( $values, $page_id, false, $content_reason );
		}
		saxon_cf_respond( $is_ajax, true, array(), array() );
	}

	saxon_cf_count_submission( $values['email'] );

	$sent = saxon_cf_send_mail( $values, $page_id );

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
 * Honeypot, signed token and timing checks.
 *
 * @param array $raw Unslashed request data.
 * @return string Reason code when the request is from a bot, or ''.
 */
function saxon_cf_is_spam( $raw ) {
	if ( ! empty( $raw['website'] ) ) {
		return 'honeypot';
	}

	// Token: "time.random.signature". The older "time.signature" form is
	// still accepted so pages loaded before an update keep working.
	$token = isset( $raw['saxon_cf_ts'] ) && is_string( $raw['saxon_cf_ts'] ) ? $raw['saxon_cf_ts'] : '';
	$parts = explode( '.', $token );
	if ( 3 === count( $parts ) && ctype_digit( $parts[0] ) && ctype_alnum( $parts[1] ) ) {
		$valid = hash_equals( substr( wp_hash( $parts[0] . '|' . $parts[1] . '|saxon_cf' ), 0, 24 ), $parts[2] );
	} elseif ( 2 === count( $parts ) && ctype_digit( $parts[0] ) ) {
		$valid = hash_equals( substr( wp_hash( $parts[0] . '|saxon_cf' ), 0, 20 ), $parts[1] );
	} else {
		$valid = false;
	}
	if ( ! $valid ) {
		return 'token';
	}

	return ( time() - (int) $parts[0] ) < SAXON_CF_MIN_SECONDS ? 'too_fast' : '';
}

/**
 * Whether the visitor has used up their submissions or failed attempts, or
 * the whole form has hit its hourly cap.
 *
 * @return bool
 */
function saxon_cf_rate_limited() {
	$ip = saxon_cf_hash( saxon_cf_ip() );
	return saxon_cf_counter_get( 'r' . $ip, SAXON_CF_RATE_WINDOW ) >= SAXON_CF_RATE_LIMIT
		|| saxon_cf_counter_get( 'f' . $ip, SAXON_CF_RATE_WINDOW ) >= SAXON_CF_FAIL_LIMIT
		|| saxon_cf_counter_get( 'all', HOUR_IN_SECONDS ) >= SAXON_CF_GLOBAL_LIMIT;
}

/**
 * Count a submission that failed validation.
 */
function saxon_cf_count_failure() {
	saxon_cf_counter_hit( 'f' . saxon_cf_hash( saxon_cf_ip() ), SAXON_CF_RATE_WINDOW );
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
 * Count a valid submission against the visitor's, the address's and the
 * form's limits. Failed validation does not count, so fixing a typo never
 * locks anyone out.
 *
 * @param string $email Sender address.
 */
function saxon_cf_count_submission( $email ) {
	saxon_cf_counter_hit( 'r' . saxon_cf_hash( saxon_cf_ip() ), SAXON_CF_RATE_WINDOW );
	saxon_cf_counter_hit( 'e' . saxon_cf_hash( $email ), HOUR_IN_SECONDS );
	saxon_cf_counter_hit( 'all', HOUR_IN_SECONDS );
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
		// Lets a mail filter recognize form messages that passed every check.
		'X-Saxon-Contact: website-form; checks=passed',
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
