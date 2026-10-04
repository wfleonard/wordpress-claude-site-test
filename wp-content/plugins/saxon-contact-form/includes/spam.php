<?php
/**
 * Spam defenses beyond the honeypot and timer: atomic rate counters, single
 * use form tokens, content and address checks, Akismet, blocked message
 * statistics, and email address protection in page content.
 *
 * Everything here is self contained; Akismet is used only if it is already
 * active with an API key.
 *
 * @package SaxonContactForm
 * @author  William Leonard, Saxon Enterprises, Inc.
 */

defined( 'ABSPATH' ) || exit;

/**
 * A form older than this (seconds) must be reloaded.
 */
const SAXON_CF_MAX_AGE = 86400;

/**
 * Valid messages allowed from one email address per hour.
 */
const SAXON_CF_EMAIL_LIMIT = 3;

/**
 * Valid messages allowed from everyone combined per hour, so a flood from
 * many addresses still cannot fill the inbox.
 */
const SAXON_CF_GLOBAL_LIMIT = 30;

/**
 * Increment a counter for the current time window and return the new value.
 *
 * Uses one INSERT ... ON DUPLICATE KEY UPDATE, so simultaneous requests on
 * several PHP workers cannot lose counts the way get then set transients can.
 *
 * @param string $name   Counter name (letters, digits, underscores).
 * @param int    $window Window length in seconds.
 * @return int
 */
function saxon_cf_counter_hit( $name, $window ) {
	global $wpdb;
	$key = saxon_cf_counter_key( $name, $window );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'off') ON DUPLICATE KEY UPDATE option_value = option_value + 1", $key ) );
	return saxon_cf_counter_get( $name, $window );
}

/**
 * Current value of a counter.
 *
 * @param string $name   Counter name.
 * @param int    $window Window length in seconds.
 * @return int
 */
function saxon_cf_counter_get( $name, $window ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", saxon_cf_counter_key( $name, $window ) ) );
}

/**
 * Option name for a counter: name, window and the window's number.
 *
 * @param string $name   Counter name.
 * @param int    $window Window length in seconds.
 * @return string
 */
function saxon_cf_counter_key( $name, $window ) {
	return 'saxon_cf_c_' . $name . '_' . (int) $window . '_' . (int) floor( time() / $window );
}

/**
 * Short salted hash used to name counters, so no address is ever stored.
 *
 * @param string $value IP address or email.
 * @return string
 */
function saxon_cf_hash( $value ) {
	return substr( wp_hash( strtolower( (string) $value ) . '|saxon_cf_rate' ), 0, 16 );
}

/**
 * The visitor's IP address as seen by the web server.
 *
 * @return string
 */
function saxon_cf_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

/**
 * Mark the submitted form token as used. Returns false when it was already
 * used, which means the same form was replayed.
 *
 * @param string $token Value of saxon_cf_ts.
 * @return bool
 */
function saxon_cf_use_token( $token ) {
	return add_option( 'saxon_cf_u_' . substr( wp_hash( $token . '|' . saxon_cf_ip() . '|saxon_cf_once' ), 0, 24 ), time(), '', false );
}

/**
 * Whether the form token is older than SAXON_CF_MAX_AGE.
 *
 * @param string $token Value of saxon_cf_ts.
 * @return bool
 */
function saxon_cf_token_expired( $token ) {
	$time = (int) strtok( (string) $token, '.' );
	return $time > 0 && time() - $time > SAXON_CF_MAX_AGE;
}

/**
 * Throwaway inbox domains that are almost never used by real enquiries.
 *
 * @return string[]
 */
function saxon_cf_disposable_domains() {
	/**
	 * Filter the list of disposable email domains.
	 *
	 * @param string[] $domains Lowercase domains.
	 */
	return apply_filters(
		'saxon_cf_disposable_domains',
		array(
			'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'guerrillamailblock.com', 'sharklasers.com',
			'grr.la', '10minutemail.com', '10minutemail.net', 'temp-mail.org', 'tempmail.com', 'tempmail.net',
			'tempmailo.com', 'yopmail.com', 'yopmail.net', 'trashmail.com', 'trashmail.de', 'getnada.com',
			'nada.email', 'dispostable.com', 'maildrop.cc', 'throwawaymail.com', 'fakeinbox.com', 'mintemail.com',
			'mohmal.com', 'emailondeck.com', 'spamgourmet.com', 'mailnesia.com', 'mytemp.email', 'tempinbox.com',
			'burnermail.io', 'mail.tm', 'moakt.com', 'tmpmail.org', 'tmpmail.net', 'inboxkitten.com', 'mailpoof.com',
			'discard.email', 'spambox.us', 'trbvm.com', 'byom.de', 'mailcatch.com', 'spam4.me', 'tempr.email',
		)
	);
}

/**
 * Check the email address domain. Returns a field error message, or ''.
 *
 * @param string $email Valid email address.
 * @return string
 */
function saxon_cf_email_problem( $email ) {
	$domain = strtolower( substr( (string) strrchr( $email, '@' ), 1 ) );

	if ( in_array( $domain, saxon_cf_disposable_domains(), true ) ) {
		return __( 'Please use a permanent email address so we can reply.', 'saxon-contact-form' );
	}

	/**
	 * Whether to check that the email domain can receive mail (DNS lookup).
	 *
	 * @param bool $check Default true.
	 */
	if ( apply_filters( 'saxon_cf_check_mx', true ) && function_exists( 'checkdnsrr' )
		&& ! checkdnsrr( $domain . '.', 'MX' ) && ! checkdnsrr( $domain . '.', 'A' ) ) {
		return __( 'That email domain does not seem to receive mail. Please check the address.', 'saxon-contact-form' );
	}

	return '';
}

/**
 * Content checks on validated values. Returns a reason code, or '' when the
 * message looks genuine.
 *
 * @param array $values Sanitized values.
 * @return string
 */
function saxon_cf_content_spam( $values ) {
	$message = (string) ( $values['message'] ?? '' );
	$name    = (string) ( $values['name'] ?? '' );
	$text    = strtolower( $message . ' ' . $name );

	// Links or addresses in the name field are a bot habit.
	if ( preg_match( '#https?://|www\.|\.(com|net|org|ru|xyz|top)\b#i', $name ) ) {
		return 'name_link';
	}

	// Forum and HTML link markup never comes from a real person typing.
	if ( preg_match( '#\[url[=\]]|\[link[=\]]|<a\s+href|\[/url\]#i', $message ) ) {
		return 'markup';
	}

	// Link shorteners hide where a link goes.
	if ( preg_match( '#\b(bit\.ly|tinyurl\.com|t\.co|goo\.gl|ow\.ly|is\.gd|cutt\.ly|rebrand\.ly|shorturl\.at|rb\.gy|t\.ly)/#i', $message ) ) {
		return 'shortener';
	}

	// Mostly non Latin letters (for an English language business).
	$letters = preg_match_all( '/\p{L}/u', $message );
	$latin   = preg_match_all( '/\p{Latin}/u', $message );
	if ( $letters >= 20 && $latin / $letters < 0.5 ) {
		return 'script';
	}

	// Phrases from unsolicited pitches, not from people asking about our
	// services: one strong phrase, or two others. Prospects asking about SEO
	// or traffic are deliberately not caught.
	$strong = array( 'casino', 'viagra', 'cialis', 'porn', 'escort service', 'buy followers', 'crypto investment', 'bitcoin investment', 'forex signals', 'payday loan' );
	$weak   = array( 'guest post', 'backlinks', 'link building', 'domain authority', 'we can help you rank', 'first page of google', 'white label', 'outsourcing partner', 'unsubscribe', 'opt out', 'reply stop', 'whatsapp me', 'telegram', 'limited time offer', 'dear website owner', 'dear sir/madam', 'i noticed your website', 'i came across your website', 'boost your sales', 'per month only' );

	/** Filter the strong spam phrases. @param string[] $strong */
	foreach ( apply_filters( 'saxon_cf_spam_phrases_strong', $strong ) as $phrase ) {
		if ( false !== strpos( $text, $phrase ) ) {
			return 'phrase';
		}
	}
	$hits = 0;
	/** Filter the weaker spam phrases. @param string[] $weak */
	foreach ( apply_filters( 'saxon_cf_spam_phrases', $weak ) as $phrase ) {
		$hits += false !== strpos( $text, $phrase ) ? 1 : 0;
	}
	if ( $hits >= 2 ) {
		return 'phrases';
	}

	return saxon_cf_akismet_spam( $values ) ? 'akismet' : '';
}

/**
 * Ask Akismet, when it is active with a key. Fails open if Akismet cannot be reached.
 *
 * @param array $values Sanitized values.
 * @return bool True when Akismet says spam.
 */
function saxon_cf_akismet_spam( $values ) {
	if ( ! class_exists( 'Akismet' ) || ! is_callable( array( 'Akismet', 'get_api_key' ) ) || ! Akismet::get_api_key() ) {
		return false;
	}

	$query = array(
		'blog'                 => home_url( '/' ),
		'user_ip'              => saxon_cf_ip(),
		'user_agent'           => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		'referrer'             => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		'permalink'            => (string) wp_get_referer(),
		'comment_type'         => 'contact-form',
		'comment_author'       => $values['name'] ?? '',
		'comment_author_email' => $values['email'] ?? '',
		'comment_content'      => $values['message'] ?? '',
		'blog_lang'            => get_locale(),
		'blog_charset'         => get_option( 'blog_charset' ),
	);

	$response = Akismet::http_post( build_query( $query ), 'comment-check' );
	return is_array( $response ) && isset( $response[1] ) && 'true' === trim( $response[1] );
}

/**
 * Count a blocked submission by reason, for the settings screen. No message
 * content or addresses are kept.
 *
 * @param string $reason Reason code.
 */
function saxon_cf_record_blocked( $reason ) {
	$stats = get_option( 'saxon_cf_spam_stats', array() );
	$stats = is_array( $stats ) ? $stats : array();
	$stats['counts'][ $reason ] = (int) ( $stats['counts'][ $reason ] ?? 0 ) + 1;
	$stats['last']              = time();
	$stats['since']             = $stats['since'] ?? time();
	update_option( 'saxon_cf_spam_stats', $stats, false );
}

/**
 * Plain language names for reason codes.
 *
 * @return string[]
 */
function saxon_cf_reason_labels() {
	return array(
		'honeypot'  => __( 'Filled the hidden field', 'saxon-contact-form' ),
		'too_fast'  => __( 'Sent too fast after loading', 'saxon-contact-form' ),
		'token'     => __( 'Missing or forged form token', 'saxon-contact-form' ),
		'replay'    => __( 'Same form sent twice', 'saxon-contact-form' ),
		'name_link' => __( 'Link in the name', 'saxon-contact-form' ),
		'markup'    => __( 'Link markup in the message', 'saxon-contact-form' ),
		'shortener' => __( 'Shortened links', 'saxon-contact-form' ),
		'script'    => __( 'Mostly non Latin text', 'saxon-contact-form' ),
		'phrase'    => __( 'Known spam phrase', 'saxon-contact-form' ),
		'phrases'   => __( 'Several sales pitch phrases', 'saxon-contact-form' ),
		'akismet'   => __( 'Akismet', 'saxon-contact-form' ),
	);
}

/**
 * Delete counters and used tokens that have expired. Runs daily.
 */
function saxon_cf_cleanup() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'saxon\\_cf\\_c\\_%' OR option_name LIKE 'saxon\\_cf\\_u\\_%' LIMIT 20000" );
	$now  = time();
	foreach ( $rows as $row ) {
		$stale = false;
		if ( 0 === strpos( $row->option_name, 'saxon_cf_u_' ) ) {
			$stale = $now - (int) $row->option_value > SAXON_CF_MAX_AGE + HOUR_IN_SECONDS;
		} elseif ( preg_match( '/_(\d+)_(\d+)$/', $row->option_name, $m ) ) {
			$stale = ( (int) $m[2] + 2 ) * (int) $m[1] < $now;
		}
		if ( $stale ) {
			delete_option( $row->option_name );
		}
	}
}
add_action( 'saxon_cf_cleanup', 'saxon_cf_cleanup' );

/**
 * Schedule the daily cleanup.
 */
function saxon_cf_schedule_cleanup() {
	if ( ! wp_next_scheduled( 'saxon_cf_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'saxon_cf_cleanup' );
	}
}
add_action( 'init', 'saxon_cf_schedule_cleanup' );

/**
 * Encode email addresses in page content so simple scrapers cannot read them.
 * Browsers show and link them normally.
 *
 * @param string $content Post content.
 * @return string
 */
function saxon_cf_protect_emails( $content ) {
	if ( false === strpos( $content, '@' ) ) {
		return $content;
	}

	// mailto: links, including their visible text.
	$content = preg_replace_callback(
		'#(href=["\'])mailto:([^"\'?]+)#i',
		static function ( $m ) {
			return $m[1] . 'mailto:' . antispambot( $m[2], 1 );
		},
		$content
	);

	// Plain addresses in text, outside tags and attributes.
	$parts = preg_split( '/(<[^>]*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( '' !== $part && '<' !== $part[0] ) {
			$parts[ $i ] = preg_replace_callback(
				'/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/',
				static function ( $m ) {
					return antispambot( $m[0] );
				},
				$part
			);
		}
	}
	return implode( '', $parts );
}
add_filter( 'the_content', 'saxon_cf_protect_emails', 20 );
