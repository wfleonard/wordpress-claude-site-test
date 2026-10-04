<?php
/**
 * Remove the plugin's settings and stored messages when it is deleted
 * from the Plugins screen. Deactivating the plugin keeps everything.
 *
 * @package SaxonContactForm
 * @author  William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'saxon_cf_options' );

$saxon_cf_ids = get_posts(
	array(
		'post_type'      => 'saxon_cf_message',
		'post_status'    => array_keys( get_post_stati() ),
		'fields'         => 'ids',
		'posts_per_page' => -1,
	)
);
foreach ( $saxon_cf_ids as $saxon_cf_id ) {
	wp_delete_post( $saxon_cf_id, true );
}

// Rate limit counters and saved form states are short lived transients.
global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_saxon_cf_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_saxon_cf_' ) . '%'
	)
);

// Spam statistics, rate counters, used form tokens and the cleanup event.
delete_option( 'saxon_cf_spam_stats' );
wp_clear_scheduled_hook( 'saxon_cf_cleanup' );
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( 'saxon_cf_c_' ) . '%',
		$wpdb->esc_like( 'saxon_cf_u_' ) . '%'
	)
);
