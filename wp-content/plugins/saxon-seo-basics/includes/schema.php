<?php
/**
 * JSON-LD structured data: Organization, WebSite, WebPage, BreadcrumbList
 * and BlogPosting, linked together in one @graph.
 *
 * @package SaxonSeoBasics
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the JSON-LD block.
 */
function saxon_seo_print_schema() {
	$graph = saxon_seo_schema_graph();
	if ( ! $graph ) {
		return;
	}

	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
	);

	if ( $json ) {
		wp_print_inline_script_tag( $json, array( 'type' => 'application/ld+json' ) );
	}
}

/**
 * Build the graph for the current request.
 *
 * @return array[]
 */
function saxon_seo_schema_graph() {
	$home    = home_url( '/' );
	$org_id  = $home . '#organization';
	$site_id = $home . '#website';
	$graph   = array( saxon_seo_schema_organization( $org_id ) );

	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $site_id,
		'url'             => $home,
		'name'            => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		'description'     => wp_strip_all_tags( get_bloginfo( 'description' ) ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'publisher'       => array( '@id' => $org_id ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => add_query_arg( 's', '{search_term_string}', $home ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	if ( is_404() || is_search() ) {
		return saxon_seo_schema_clean( $graph );
	}

	$context = saxon_seo_context();
	if ( '' === $context['url'] ) {
		return saxon_seo_schema_clean( $graph );
	}

	$page_id     = $context['url'] . '#webpage';
	$crumbs      = saxon_seo_breadcrumbs();
	$is_singular = is_singular() && ! is_front_page();
	$page        = array(
		'@type'       => is_front_page() || $is_singular ? 'WebPage' : 'CollectionPage',
		'@id'         => $page_id,
		'url'         => $context['url'],
		'name'        => $context['title'],
		'description' => $context['description'],
		'inLanguage'  => get_bloginfo( 'language' ),
		'isPartOf'    => array( '@id' => $site_id ),
	);

	if ( is_front_page() ) {
		$page['about'] = array( '@id' => $org_id );
	}
	if ( $context['image'] ) {
		$page['primaryImageOfPage'] = saxon_seo_schema_image( $context['image'] );
	}
	if ( $is_singular ) {
		$post                  = get_queried_object();
		$page['datePublished'] = get_post_time( DATE_W3C, true, $post );
		$page['dateModified']  = get_post_modified_time( DATE_W3C, true, $post );
		if ( is_page_template( 'page-templates/contact.php' ) ) {
			$page['@type'] = array( 'WebPage', 'ContactPage' );
		} elseif ( is_page_template( 'page-templates/about.php' ) ) {
			$page['@type'] = array( 'WebPage', 'AboutPage' );
		}
	}
	if ( count( $crumbs ) > 1 ) {
		$page['breadcrumb'] = array( '@id' => $context['url'] . '#breadcrumb' );
	}
	$graph[] = $page;

	if ( count( $crumbs ) > 1 ) {
		$items = array();
		foreach ( $crumbs as $i => $crumb ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb['name'],
			);
			// The last item is the current page, which Google prefers without a URL.
			if ( $i < count( $crumbs ) - 1 ) {
				$item['item'] = $crumb['url'];
			}
			$items[] = $item;
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $context['url'] . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	if ( is_singular( 'post' ) ) {
		$post    = get_queried_object();
		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => $context['url'] . '#article',
			'headline'         => saxon_seo_trim( $context['title'], 110 ),
			'description'      => $context['description'],
			'datePublished'    => get_post_time( DATE_W3C, true, $post ),
			'dateModified'     => get_post_modified_time( DATE_W3C, true, $post ),
			'mainEntityOfPage' => array( '@id' => $page_id ),
			'isPartOf'         => array( '@id' => $page_id ),
			'publisher'        => array( '@id' => $org_id ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
				'url'   => get_author_posts_url( (int) $post->post_author ),
			),
			'inLanguage'       => get_bloginfo( 'language' ),
		);
		if ( $context['image'] ) {
			$article['image'] = saxon_seo_schema_image( $context['image'] );
		}
		$categories = get_the_category( $post->ID );
		if ( $categories ) {
			$article['articleSection'] = wp_list_pluck( $categories, 'name' );
		}
		$graph[] = $article;
	}

	/**
	 * Filter the JSON-LD graph before it is printed.
	 *
	 * @param array[] $graph Graph nodes.
	 */
	return saxon_seo_schema_clean( apply_filters( 'saxon_seo_schema_graph', $graph ) );
}

/**
 * The Organization node, falling back to the theme's contact details.
 *
 * @param string $id Node id.
 * @return array
 */
function saxon_seo_schema_organization( $id ) {
	$name  = (string) saxon_seo_option( 'org_name' );
	$phone = (string) saxon_seo_option( 'org_phone' );
	$email = (string) saxon_seo_option( 'org_email' );

	$org = array(
		'@type' => saxon_seo_option( 'org_type' ),
		'@id'   => $id,
		'name'  => '' !== $name ? $name : wp_strip_all_tags( get_bloginfo( 'name' ) ),
		'url'   => home_url( '/' ),
	);

	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : false;
	if ( $logo ) {
		$org['logo']  = array(
			'@type'  => 'ImageObject',
			'@id'    => home_url( '/' ) . '#logo',
			'url'    => $logo[0],
			'width'  => (int) $logo[1],
			'height' => (int) $logo[2],
		);
		$org['image'] = array( '@id' => home_url( '/' ) . '#logo' );
	}

	// The Saxon theme keeps contact details in the Customizer.
	$phone = '' !== $phone ? $phone : (string) get_theme_mod( 'saxon_contact_phone', '' );
	$email = '' !== $email ? $email : (string) get_theme_mod( 'saxon_contact_email', '' );
	if ( '' !== $phone ) {
		$org['telephone'] = $phone;
	}
	if ( '' !== $email && is_email( $email ) ) {
		$org['email'] = $email;
	}

	$social = (array) saxon_seo_option( 'social' );
	if ( $social ) {
		$org['sameAs'] = array_values( $social );
	}

	return $org;
}

/**
 * ImageObject for a share image.
 *
 * @param array $image { url, width, height, alt }.
 * @return array
 */
function saxon_seo_schema_image( $image ) {
	return array(
		'@type'   => 'ImageObject',
		'url'     => $image['url'],
		'width'   => $image['width'],
		'height'  => $image['height'],
		'caption' => $image['alt'],
	);
}

/**
 * Breadcrumb trail for the current request: Home, parents, current.
 *
 * @return array[] { name, url }
 */
function saxon_seo_breadcrumbs() {
	if ( is_front_page() ) {
		return array();
	}

	$crumbs = array(
		array(
			'name' => __( 'Home', 'saxon-seo-basics' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( is_singular() ) {
		$post = get_queried_object();

		if ( 'post' === $post->post_type ) {
			$blog = (int) get_option( 'page_for_posts' );
			if ( $blog ) {
				$crumbs[] = array(
					'name' => get_the_title( $blog ),
					'url'  => get_permalink( $blog ),
				);
			}
		}

		foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
			$crumbs[] = array(
				'name' => wp_strip_all_tags( get_the_title( $ancestor ) ),
				'url'  => get_permalink( $ancestor ),
			);
		}
	}

	$context  = saxon_seo_context();
	$crumbs[] = array(
		'name' => is_singular() ? wp_strip_all_tags( get_the_title( get_queried_object() ) ) : $context['title'],
		'url'  => $context['url'],
	);

	return $crumbs;
}

/**
 * Drop empty values so the JSON stays tidy.
 *
 * @param array $data Data.
 * @return array
 */
function saxon_seo_schema_clean( $data ) {
	$is_list = array() === $data || array_keys( $data ) === range( 0, count( $data ) - 1 );
	foreach ( $data as $key => $value ) {
		if ( is_array( $value ) ) {
			$value        = saxon_seo_schema_clean( $value );
			$data[ $key ] = $value;
		}
		if ( '' === $value || null === $value || array() === $value ) {
			unset( $data[ $key ] );
		}
	}
	return $is_list ? array_values( $data ) : $data;
}
