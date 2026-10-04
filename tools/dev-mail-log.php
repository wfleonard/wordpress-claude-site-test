<?php
/**
 * Plugin Name: Dev mail log (local only)
 * Description: Writes every wp_mail() call to wp-content/dev-mail.log instead of sending it. For local testing; never install on a live site.
 *
 * Install by copying or linking into wp-content/mu-plugins/.
 *
 * @author William Leonard, Saxon Enterprises, Inc.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pre_wp_mail',
	static function ( $short_circuit, $atts ) {
		$entry = array(
			'time'    => gmdate( 'c' ),
			'to'      => $atts['to'],
			'subject' => $atts['subject'],
			'headers' => $atts['headers'],
			'message' => $atts['message'],
		);
		file_put_contents( WP_CONTENT_DIR . '/dev-mail.log', wp_json_encode( $entry ) . "\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return true;
	},
	10,
	2
);
