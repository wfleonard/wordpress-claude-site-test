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
		'post_status'    => 'any',
		'fields'         => 'ids',
		'posts_per_page' => -1,
	)
);
foreach ( $saxon_cf_ids as $saxon_cf_id ) {
	wp_delete_post( $saxon_cf_id, true );
}
