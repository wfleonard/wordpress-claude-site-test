<?php
/**
 * Small adjustments to the XML sitemap WordPress core already provides at
 * /wp-sitemap.xml. No second sitemap is generated.
 *
 * @package SaxonSeoBasics
 */

defined( 'ABSPATH' ) || exit;

/**
 * Leave pages marked noindex out of the sitemap.
 *
 * @param array $args Query args.
 * @return array
 */
function saxon_seo_sitemap_query( $args ) {
	$args['meta_query']   = isset( $args['meta_query'] ) ? (array) $args['meta_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
	$args['meta_query'][] = array(
		'relation' => 'OR',
		array(
			'key'     => '_saxon_seo_noindex',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_saxon_seo_noindex',
			'value'   => '1',
			'compare' => '!=',
		),
	);
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'saxon_seo_sitemap_query' );

/**
 * Add the last modified date to each post entry, which core leaves out.
 *
 * @param array   $entry Sitemap entry.
 * @param WP_Post $post  Post.
 * @return array
 */
function saxon_seo_sitemap_lastmod( $entry, $post ) {
	$entry['lastmod'] = get_post_modified_time( DATE_W3C, true, $post );
	return $entry;
}
add_filter( 'wp_sitemaps_posts_entry', 'saxon_seo_sitemap_lastmod', 10, 2 );

/**
 * Drop the author archive sitemap. On a business site the author pages
 * repeat the blog and add nothing for search.
 *
 * @param WP_Sitemaps_Provider|false $provider Provider.
 * @param string                     $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function saxon_seo_sitemap_providers( $provider, $name ) {
	/**
	 * Filter whether to keep the users (author) sitemap.
	 *
	 * @param bool $keep Keep it. Default false.
	 */
	if ( 'users' === $name && ! apply_filters( 'saxon_seo_keep_user_sitemap', false ) ) {
		return false;
	}
	return $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'saxon_seo_sitemap_providers', 10, 2 );
