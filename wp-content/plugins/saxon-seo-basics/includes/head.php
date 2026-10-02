<?php
/**
 * Head tags: meta description, Open Graph, Twitter cards, SEO titles and
 * the per page noindex flag.
 *
 * @package SaxonSeoBasics
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the meta tags.
 */
function saxon_seo_print_head() {
	if ( is_404() || is_search() ) {
		return;
	}

	$context = saxon_seo_context();
	$tags    = array();

	if ( '' !== $context['description'] ) {
		$tags[] = array( 'name', 'description', $context['description'] );
	}

	$tags[] = array( 'property', 'og:locale', get_locale() );
	$tags[] = array( 'property', 'og:site_name', wp_strip_all_tags( get_bloginfo( 'name' ) ) );
	$tags[] = array( 'property', 'og:type', $context['type'] );
	$tags[] = array( 'property', 'og:title', $context['title'] );
	if ( '' !== $context['description'] ) {
		$tags[] = array( 'property', 'og:description', $context['description'] );
	}
	if ( '' !== $context['url'] ) {
		$tags[] = array( 'property', 'og:url', $context['url'] );
	}

	$image = $context['image'];
	if ( $image ) {
		$tags[] = array( 'property', 'og:image', $image['url'] );
		$tags[] = array( 'property', 'og:image:width', (string) $image['width'] );
		$tags[] = array( 'property', 'og:image:height', (string) $image['height'] );
		if ( '' !== $image['alt'] ) {
			$tags[] = array( 'property', 'og:image:alt', $image['alt'] );
		}
	}

	if ( 'article' === $context['type'] ) {
		$tags[] = array( 'property', 'article:published_time', get_the_date( DATE_W3C, get_queried_object_id() ) );
		$tags[] = array( 'property', 'article:modified_time', get_the_modified_date( DATE_W3C, get_queried_object_id() ) );
	}

	$tags[] = array( 'name', 'twitter:card', $image && $image['width'] >= 300 ? 'summary_large_image' : 'summary' );
	$site   = (string) saxon_seo_option( 'twitter_site' );
	if ( '' !== $site ) {
		$tags[] = array( 'name', 'twitter:site', '@' . $site );
	}

	echo "\n<!-- Saxon SEO Basics -->\n";
	foreach ( $tags as $tag ) {
		if ( '' === (string) $tag[2] ) {
			continue;
		}
		printf( '<meta %1$s="%2$s" content="%3$s" />' . "\n", esc_attr( $tag[0] ), esc_attr( $tag[1] ), esc_attr( $tag[2] ) );
	}
}

/**
 * Use the SEO title in the document title when one is set.
 *
 * @param array $parts Title parts.
 * @return array
 */
function saxon_seo_title_parts( $parts ) {
	if ( is_singular() && ! is_front_page() ) {
		$title = (string) get_post_meta( get_queried_object_id(), '_saxon_seo_title', true );
		if ( '' !== $title ) {
			$parts['title'] = $title;
		}
	}
	return $parts;
}

/**
 * Add noindex to the robots meta core prints, when the page asks for it.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function saxon_seo_robots( $robots ) {
	if ( is_singular() && get_post_meta( get_queried_object_id(), '_saxon_seo_noindex', true ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
}
