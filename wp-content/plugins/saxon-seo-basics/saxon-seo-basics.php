<?php
/**
 * Plugin Name:       Saxon SEO Basics
 * Description:       Fills the gaps WordPress core leaves: meta descriptions, Open Graph and Twitter tags, JSON-LD schema, per page noindex and SEO titles. Core still provides the title tag, canonical links, robots meta and the XML sitemap.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            William Leonard, CTI Global
 * Author URI:        mailto:bill.leonard@cticorp.com
 * License:           GPL-2.0-or-later
 * Text Domain:       saxon-seo-basics
 *
 * @package SaxonSeoBasics
 * @author  William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

defined( 'ABSPATH' ) || exit;

define( 'SAXON_SEO_VERSION', '1.0.0' );
define( 'SAXON_SEO_FILE', __FILE__ );
define( 'SAXON_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAXON_SEO_URL', plugin_dir_url( __FILE__ ) );

require SAXON_SEO_DIR . 'includes/settings.php';
require SAXON_SEO_DIR . 'includes/post-meta.php';
require SAXON_SEO_DIR . 'includes/context.php';
require SAXON_SEO_DIR . 'includes/head.php';
require SAXON_SEO_DIR . 'includes/schema.php';
require SAXON_SEO_DIR . 'includes/sitemap.php';

/**
 * Whether a full SEO plugin is active. If so this plugin stays quiet on the
 * front end so the page never gets two sets of tags.
 *
 * @return bool
 */
function saxon_seo_conflict() {
	$conflict = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'The_SEO_Framework\Load', false );

	/**
	 * Filter whether another SEO plugin owns the head tags.
	 *
	 * @param bool $conflict Detected conflict.
	 */
	return (bool) apply_filters( 'saxon_seo_conflict', $conflict );
}

/**
 * Hook the front end output once plugins are loaded.
 */
function saxon_seo_boot() {
	load_plugin_textdomain( 'saxon-seo-basics', false, dirname( plugin_basename( SAXON_SEO_FILE ) ) . '/languages' );

	if ( saxon_seo_conflict() ) {
		add_action( 'admin_notices', 'saxon_seo_conflict_notice' );
		return;
	}

	add_action( 'wp_head', 'saxon_seo_print_head', 1 );
	add_action( 'wp_head', 'saxon_seo_print_schema', 30 );
	add_filter( 'document_title_parts', 'saxon_seo_title_parts' );
	add_filter( 'wp_robots', 'saxon_seo_robots' );
}
add_action( 'plugins_loaded', 'saxon_seo_boot' );

/**
 * Tell admins why nothing is printed.
 */
function saxon_seo_conflict_notice() {
	$screen = get_current_screen();
	if ( ! current_user_can( 'manage_options' ) || ! $screen || 'settings_page_saxon-seo-basics' !== $screen->id ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Another SEO plugin is active, so Saxon SEO Basics is not adding any tags. Deactivate one of the two.', 'saxon-seo-basics' )
	);
}

/**
 * Settings link on the Plugins screen.
 *
 * @param string[] $links Action links.
 * @return string[]
 */
function saxon_seo_action_links( $links ) {
	array_unshift(
		$links,
		sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=saxon-seo-basics' ) ), esc_html__( 'Settings', 'saxon-seo-basics' ) )
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( SAXON_SEO_FILE ), 'saxon_seo_action_links' );
