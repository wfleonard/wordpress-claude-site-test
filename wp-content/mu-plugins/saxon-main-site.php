<?php
/**
 * Plugin Name: Saxon Enterprises main site
 * Description: Presents saxonwordpress.com as the WordPress workshop of Saxon Enterprises, Inc., not a separate business: the structured data names the same organization as saxonenterprises.net, pages that repeat the main site point their canonical there, and a banner links to it.
 * Author: Saxon Enterprises, Inc.
 *
 * Must use plugin: upload to wp-content/mu-plugins/. Nothing to activate.
 *
 * @package SaxonMainSite
 */

defined( 'ABSPATH' ) || exit;

const SAXON_MAIN_URL = 'https://saxonenterprises.net';

/**
 * The business entity, identical to the one published on saxonenterprises.net so AI
 * assistants and search engines resolve both sites to one company.
 *
 * @return array
 */
function saxon_main_org() {
	return array(
		'@type'         => array( 'ProfessionalService', 'Organization' ),
		'@id'           => SAXON_MAIN_URL . '/#org',
		'name'          => 'Saxon Enterprises, Inc.',
		'alternateName' => 'Saxon Enterprises',
		'url'           => SAXON_MAIN_URL . '/',
		'logo'          => SAXON_MAIN_URL . '/assets/img/saxon-enterprises-logo.png',
		'telephone'     => '+1-732-673-4260',
		'email'         => 'wfleonard@saxonenterprises.net',
		'address'       => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Tinton Falls',
			'addressRegion'   => 'NJ',
			'postalCode'      => '07724',
			'addressCountry'  => 'US',
		),
		'sameAs'        => array( 'https://www.linkedin.com/in/billleonard/' ),
	);
}

/**
 * Swap Saxon SEO Basics' local Organization node for the main one and repoint
 * every reference to it.
 */
add_filter(
	'saxon_seo_schema_graph',
	function ( $graph ) {
		$local_id = home_url( '/' ) . '#organization';
		$main_ref = array( '@id' => SAXON_MAIN_URL . '/#org' );
		$out      = array();

		foreach ( $graph as $node ) {
			if ( isset( $node['@id'] ) && $node['@id'] === $local_id ) {
				$out[] = saxon_main_org();
				continue;
			}
			foreach ( array( 'publisher', 'about', 'copyrightHolder' ) as $key ) {
				if ( isset( $node[ $key ]['@id'] ) && $node[ $key ]['@id'] === $local_id ) {
					$node[ $key ] = $main_ref;
				}
			}
			if ( isset( $node['@type'] ) && 'WebSite' === $node['@type'] ) {
				$node['name']        = 'Saxon WordPress Training, a Saxon Enterprises site';
				$node['description'] = 'The WordPress workshop of Saxon Enterprises, Inc., Tinton Falls, NJ. Main site: saxonenterprises.net';
			}
			$out[] = $node;
		}
		return $out;
	}
);

/**
 * Pages that repeat saxonenterprises.net content defer to it. Blog posts are original
 * and keep their own canonical.
 */
add_filter(
	'get_canonical_url',
	function ( $url, $post ) {
		if ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
			return SAXON_MAIN_URL . '/';
		}
		if ( 'page' !== $post->post_type ) {
			return $url;
		}
		$map = array(
			'work'         => '/work/',
			'services'     => '/services/',
			'pricing-page' => '/pricing/',
			'about-us'     => '/about/',
			'contact'      => '/contact/',
		);
		$slug = get_page_uri( $post );
		return isset( $map[ $slug ] ) ? SAXON_MAIN_URL . $map[ $slug ] : $url;
	},
	10,
	2
);

/**
 * A slim banner above the header on every front-end page.
 */
add_action(
	'wp_body_open',
	function () {
		printf(
			'<div class="saxon-main-banner" style="background:#14202e;color:#fff;font-size:.875rem;line-height:1.5;text-align:center;padding:.55rem 1rem;">%s <a href="%s" style="color:#f1dfe0;font-weight:600;text-decoration:underline;">%s</a></div>',
			wp_kses( __( 'Saxon WordPress Training is the WordPress workshop of <strong>Saxon Enterprises, Inc.</strong> in Tinton Falls, NJ.', 'saxon' ), array( 'strong' => array() ) ),
			esc_url( SAXON_MAIN_URL . '/' ),
			esc_html__( 'Visit our main site, saxonenterprises.net →', 'saxon' )
		);
	}
);
