<?php
/**
 * Seed the starter pages, menus and reading settings for the Saxon theme.
 *
 * Safe to run more than once: pages are matched by slug and only created
 * when missing. Run from the command line on a development site:
 *
 *   php tools/seed-content.php /path/to/wordpress [--demo-contact]
 *
 * or with WP-CLI:  wp eval-file tools/seed-content.php
 *
 * Do not run against production without a database backup.
 *
 * @author William Leonard, Saxon Enterprises, Inc.
 */

if ( ! defined( 'ABSPATH' ) ) {
	if ( PHP_SAPI !== 'cli' ) {
		exit( 1 );
	}
	$saxon_root = $argv[1] ?? getcwd();
	require rtrim( $saxon_root, '/' ) . '/wp-load.php';
}

$saxon_demo_contact = in_array( '--demo-contact', $GLOBALS['argv'] ?? array(), true );

/**
 * Create a page if no page with the slug exists. Returns the page ID.
 */
function saxon_seed_page( $slug, $title, $content, $args = array() ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! empty( $args['post_parent'] ) ) {
		$parent   = get_post( $args['post_parent'] );
		$existing = get_page_by_path( $parent->post_name . '/' . $slug, OBJECT, 'page' );
	}
	if ( $existing ) {
		echo "exists: {$slug}\n";
		return $existing->ID;
	}

	$id = wp_insert_post(
		array_merge(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_content' => $content,
			),
			$args
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		fwrite( STDERR, $id->get_error_message() . "\n" );
		exit( 1 );
	}

	echo "created: {$slug} ({$id})\n";
	return $id;
}

$saxon_home = <<<'HTML'
<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">How we help</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Three ways we work with clients, from first conversation to long term support.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Advise</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Clear assessments and roadmaps that tie technology choices to business outcomes.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Build</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Hands on delivery by a small, senior team that stays with the work until it is done.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Support</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Ongoing care, training and improvements so your systems keep pace with your business.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML;

$saxon_about = <<<'HTML'
<!-- wp:paragraph -->
<p>Saxon Enterprises was founded on a simple idea: organizations deserve straight answers and partners who deliver. We combine practical experience with a steady, transparent way of working.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What we believe</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Clarity</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Plain language, honest estimates and no surprises.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Ownership</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We treat your goals as our own and stay accountable for results.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Craft</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Careful, well documented work that is easy to maintain.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Our story</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Replace this section with the history of the company, the people behind it and the clients it serves.</p>
<!-- /wp:paragraph -->
HTML;

$saxon_services = <<<'HTML'
<!-- wp:paragraph -->
<p>Every engagement starts with listening. We shape the work around your goals, timeline and budget, then deliver it in clear, measurable steps.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Discovery workshops and current state assessments</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Solution design, implementation and integration</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Training, documentation and ongoing support</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
HTML;

$saxon_contact = <<<'HTML'
<!-- wp:paragraph -->
<p>We would love to hear about your project. Reach out using the details on this page and we will reply within one business day.</p>
<!-- /wp:paragraph -->

<!-- wp:saxon/contact-form /-->
HTML;

$saxon_home_id     = saxon_seed_page( 'home', 'Home', $saxon_home );
$saxon_about_id    = saxon_seed_page( 'about', 'About', $saxon_about, array( 'menu_order' => 10, 'post_excerpt' => 'Who we are, what we value and how we work.' ) );
$saxon_services_id = saxon_seed_page( 'services', 'Services', $saxon_services, array( 'menu_order' => 20, 'post_excerpt' => 'Practical help to plan, build and run the systems your business depends on.' ) );
$saxon_contact_id  = saxon_seed_page( 'contact', 'Contact', $saxon_contact, array( 'menu_order' => 40, 'post_excerpt' => 'Tell us what you are working on.' ) );
$saxon_news_id     = saxon_seed_page( 'news', 'News', '', array( 'menu_order' => 30 ) );

$saxon_children = array(
	'consulting'     => array( 'Consulting', 'Assessments, strategy and roadmaps grounded in how your business really works.' ),
	'implementation' => array( 'Implementation', 'Design, build and integration delivered by a senior, hands on team.' ),
	'managed-support' => array( 'Managed support', 'Monitoring, maintenance and improvements after go live.' ),
);
$saxon_order = 1;
foreach ( $saxon_children as $saxon_slug => $saxon_child ) {
	saxon_seed_page(
		$saxon_slug,
		$saxon_child[0],
		'<!-- wp:paragraph --><p>' . esc_html( $saxon_child[1] ) . ' Replace this text with the full service description.</p><!-- /wp:paragraph -->',
		array(
			'post_parent'  => $saxon_services_id,
			'menu_order'   => $saxon_order++,
			'post_excerpt' => $saxon_child[1],
		)
	);
}

update_post_meta( $saxon_about_id, '_wp_page_template', 'page-templates/about.php' );
update_post_meta( $saxon_services_id, '_wp_page_template', 'page-templates/services.php' );
update_post_meta( $saxon_contact_id, '_wp_page_template', 'page-templates/contact.php' );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $saxon_home_id );
update_option( 'page_for_posts', $saxon_news_id );

if ( ! get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
}
flush_rewrite_rules();

/**
 * Create a menu with the given page IDs if it does not exist yet, and assign it.
 */
function saxon_seed_menu( $name, $location, $page_ids ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );
		foreach ( $page_ids as $page_id ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object-id' => $page_id,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		echo "created menu: {$name}\n";
	} else {
		$menu_id = $menu->term_id;
		echo "exists menu: {$name}\n";
	}

	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

saxon_seed_menu( 'Primary', 'primary', array( $saxon_home_id, $saxon_about_id, $saxon_services_id, $saxon_news_id, $saxon_contact_id ) );
saxon_seed_menu( 'Footer', 'footer', array( $saxon_about_id, $saxon_services_id, $saxon_contact_id ) );

if ( $saxon_demo_contact ) {
	set_theme_mod( 'saxon_contact_phone', '(555) 010 0100' );
	set_theme_mod( 'saxon_contact_email', 'hello@example.com' );
	set_theme_mod( 'saxon_contact_address', "123 Example Street\nSuite 100\nAnytown, NJ 00000" );
	echo "demo contact details set\n";
}

echo "done\n";
