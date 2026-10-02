<?php
/**
 * Works out the title, description, URL and image for the current request.
 * Shared by the head tags and the schema.
 *
 * @package SaxonSeoBasics
 */

defined( 'ABSPATH' ) || exit;

/**
 * Data for the current request, computed once.
 *
 * @return array { title, description, url, image, type, noindex }
 */
function saxon_seo_context() {
	static $context = null;
	if ( null !== $context ) {
		return $context;
	}

	$context = array(
		'title'       => '',
		'description' => '',
		'url'         => '',
		'image'       => null,
		'type'        => 'website',
	);

	$tagline = get_bloginfo( 'description', 'display' );

	if ( is_front_page() ) {
		$post_id                = is_page() ? get_queried_object_id() : 0;
		$context['title']       = get_bloginfo( 'name' );
		$context['url']         = home_url( '/' );
		$context['description'] = saxon_seo_option( 'home_description' );
		if ( '' === $context['description'] && $post_id ) {
			$context['description'] = saxon_seo_post_description( get_post( $post_id ) );
		}
		if ( '' === $context['description'] ) {
			$context['description'] = $tagline;
		}
		$context['image'] = saxon_seo_post_image( $post_id );
	} elseif ( is_singular() ) {
		$post                   = get_queried_object();
		$context['title']       = saxon_seo_post_title( $post );
		$context['description'] = saxon_seo_post_description( $post );
		$context['url']         = (string) wp_get_canonical_url( $post );
		$context['image']       = saxon_seo_post_image( $post->ID );
		$context['type']        = 'post' === $post->post_type ? 'article' : 'website';
	} elseif ( is_home() ) {
		$page_id                = (int) get_option( 'page_for_posts' );
		$context['title']       = $page_id ? saxon_seo_post_title( get_post( $page_id ) ) : get_bloginfo( 'name' );
		$context['description'] = $page_id ? saxon_seo_post_description( get_post( $page_id ) ) : '';
		$context['description'] = '' !== $context['description'] ? $context['description'] : $tagline;
		$context['url']         = $page_id ? (string) get_permalink( $page_id ) : home_url( '/' );
		$context['image']       = saxon_seo_post_image( $page_id );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term                   = get_queried_object();
		$context['title']       = single_term_title( '', false );
		$context['description'] = saxon_seo_trim( term_description( $term ) );
		$link                   = get_term_link( $term );
		$context['url']         = is_wp_error( $link ) ? '' : $link;
		$context['image']       = saxon_seo_post_image( 0 );
	} elseif ( is_post_type_archive() ) {
		$context['title']       = post_type_archive_title( '', false );
		$context['description'] = saxon_seo_trim( get_the_archive_description() );
		$context['url']         = (string) get_post_type_archive_link( get_query_var( 'post_type' ) );
		$context['image']       = saxon_seo_post_image( 0 );
	} elseif ( is_author() ) {
		$author                 = get_queried_object();
		$context['title']       = $author->display_name;
		$context['description'] = saxon_seo_trim( get_the_author_meta( 'description', $author->ID ) );
		$context['url']         = get_author_posts_url( $author->ID );
		$context['image']       = saxon_seo_post_image( 0 );
	}

	if ( $context['url'] && is_paged() && ! is_singular() ) {
		// Paged archives keep their own URL so they do not all claim page one.
		$context['url'] = get_pagenum_link( (int) get_query_var( 'paged' ) );
	}

	/**
	 * Filter the computed SEO context for the current request.
	 *
	 * @param array $context { title, description, url, image, type }.
	 */
	$context = apply_filters( 'saxon_seo_context', $context );
	return $context;
}

/**
 * Title for a post: the SEO title when set, otherwise the post title.
 *
 * @param WP_Post|null $post Post.
 * @return string
 */
function saxon_seo_post_title( $post ) {
	if ( ! $post ) {
		return '';
	}
	$title = (string) get_post_meta( $post->ID, '_saxon_seo_title', true );
	return '' !== $title ? $title : wp_strip_all_tags( get_the_title( $post ) );
}

/**
 * Description for a post: the SEO description, the manual excerpt, then the
 * start of the content.
 *
 * @param WP_Post|null $post Post.
 * @return string
 */
function saxon_seo_post_description( $post ) {
	if ( ! $post || post_password_required( $post ) ) {
		return '';
	}

	$description = (string) get_post_meta( $post->ID, '_saxon_seo_description', true );
	if ( '' !== $description ) {
		return $description;
	}
	if ( has_excerpt( $post ) ) {
		return saxon_seo_trim( $post->post_excerpt );
	}

	$content = excerpt_remove_blocks( strip_shortcodes( $post->post_content ) );
	// End headings with a full stop so they do not run into the next sentence.
	$content = preg_replace( '#</h[1-6]>#i', '$0. ', $content );
	return saxon_seo_trim( $content );
}

/**
 * Plain text cut to about 155 characters at a word boundary.
 *
 * @param string $text Text or HTML.
 * @param int    $max  Maximum length.
 * @return string
 */
function saxon_seo_trim( $text, $max = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut   = mb_substr( $text, 0, $max );
	$space = mb_strrpos( $cut, ' ' );
	return rtrim( false !== $space ? mb_substr( $cut, 0, $space ) : $cut, " ,.;:" ) . '…';
}

/**
 * Share image: the featured image, the default share image, the custom logo,
 * then the site icon.
 *
 * @param int $post_id Post ID or 0.
 * @return array|null { url, width, height, alt }
 */
function saxon_seo_post_image( $post_id ) {
	$candidates = array();
	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		$candidates[] = (int) get_post_thumbnail_id( $post_id );
	}
	$candidates[] = (int) saxon_seo_option( 'default_image' );
	$candidates[] = (int) get_theme_mod( 'custom_logo' );
	$candidates[] = (int) get_option( 'site_icon' );

	foreach ( array_filter( $candidates ) as $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
				'alt'    => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			);
		}
	}
	return null;
}
