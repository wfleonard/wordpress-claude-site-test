<?php
/**
 * Remove the plugin's settings and per page SEO fields when it is deleted
 * from the Plugins screen. Deactivating the plugin keeps everything.
 *
 * @package SaxonSeoBasics
 * @author  William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'saxon_seo_options' );

foreach ( array( '_saxon_seo_title', '_saxon_seo_description', '_saxon_seo_noindex' ) as $saxon_seo_key ) {
	delete_post_meta_by_key( $saxon_seo_key );
}
