<?php
/**
 * Reusable template helpers.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the site logo, or the site name when no logo is set.
 */
function saxon_site_branding() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}

	// A paragraph, not a heading: each template owns its single h1.
	printf(
		'<p class="site-title"><a href="%1$s" rel="home">%2$s</a></p>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);

	$description = get_bloginfo( 'description', 'display' );
	if ( $description ) {
		printf( '<p class="site-description">%s</p>', esc_html( $description ) );
	}
}

/**
 * Fallback for the primary menu when none is assigned: list top level pages.
 *
 * @param array $args wp_nav_menu arguments.
 */
function saxon_menu_fallback( $args ) {
	$pages = get_pages(
		array(
			'parent'      => 0,
			'sort_column' => 'menu_order,post_title',
			'number'      => 6,
		)
	);

	echo '<ul id="' . esc_attr( $args['menu_id'] ?? 'primary-menu' ) . '" class="' . esc_attr( $args['menu_class'] ?? 'menu' ) . '">';
	printf(
		'<li class="menu-item%1$s"><a href="%2$s"%3$s>%4$s</a></li>',
		is_front_page() ? ' current-menu-item' : '',
		esc_url( home_url( '/' ) ),
		is_front_page() ? ' aria-current="page"' : '',
		esc_html__( 'Home', 'saxon' )
	);
	foreach ( $pages as $page ) {
		if ( (int) get_option( 'page_on_front' ) === $page->ID ) {
			continue;
		}
		$current = is_page( $page->ID );
		printf(
			'<li class="menu-item%1$s"><a href="%2$s"%3$s>%4$s</a></li>',
			$current ? ' current-menu-item' : '',
			esc_url( get_permalink( $page ) ),
			$current ? ' aria-current="page"' : '',
			esc_html( get_the_title( $page ) )
		);
	}
	echo '</ul>';
}

/**
 * Post date and author line.
 */
function saxon_posted_on() {
	$time = sprintf(
		'<time class="entry-date" datetime="%1$s">%2$s</time>',
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);

	$author = get_the_author();
	$byline = '';
	if ( $author ) {
		$byline = ' <span aria-hidden="true">&middot;</span> ' . sprintf(
			/* translators: %s: author name. */
			esc_html__( 'By %s', 'saxon' ),
			'<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( $author ) . '</a>'
		);
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parts escaped above.
	echo '<p class="entry-meta">' . $time . $byline . '</p>';
}

/**
 * Print the business contact details from the Customizer as a list.
 *
 * @param string $class Extra class for the wrapper.
 */
function saxon_contact_details( $class = '' ) {
	$phone   = saxon_option( 'contact_phone' );
	$email   = saxon_option( 'contact_email' );
	$address = saxon_option( 'contact_address' );
	$hours   = saxon_option( 'contact_hours' );

	if ( ! $phone && ! $email && ! $address && ! $hours ) {
		return;
	}

	echo '<dl class="contact-details ' . esc_attr( $class ) . '">';
	if ( $phone ) {
		printf(
			'<div><dt>%1$s</dt><dd><a href="tel:%2$s">%3$s</a></dd></div>',
			esc_html__( 'Phone', 'saxon' ),
			esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ),
			esc_html( $phone )
		);
	}
	if ( $email ) {
		printf(
			'<div><dt>%1$s</dt><dd><a href="%2$s">%3$s</a></dd></div>',
			esc_html__( 'Email', 'saxon' ),
			esc_url( 'mailto:' . antispambot( $email ) ),
			esc_html( antispambot( $email ) )
		);
	}
	if ( $address ) {
		printf(
			'<div><dt>%1$s</dt><dd><address>%2$s</address></dd></div>',
			esc_html__( 'Address', 'saxon' ),
			nl2br( esc_html( $address ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped first.
		);
	}
	if ( $hours ) {
		printf(
			'<div><dt>%1$s</dt><dd>%2$s</dd></div>',
			esc_html__( 'Hours', 'saxon' ),
			esc_html( $hours )
		);
	}
	echo '</dl>';
}

/**
 * Resolve a Customizer link that may be site relative (for example "/contact/").
 *
 * @param string $url Stored value.
 * @return string
 */
function saxon_resolve_link( $url ) {
	if ( '' === $url ) {
		return '';
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
}

/**
 * Account links in the header: "Log in" for visitors, "My account" and "Log out" for signed in users.
 * Both return to the page the visitor was on.
 */
function saxon_header_account() {
	global $wp;
	$here = home_url( add_query_arg( array(), $wp->request ? trailingslashit( $wp->request ) : '' ) );

	if ( is_user_logged_in() ) {
		printf(
			'<a class="header-account__link" href="%1$s">%2$s</a><a class="button button--small button--outline" href="%3$s">%4$s</a>',
			esc_url( admin_url( 'profile.php' ) ),
			esc_html__( 'My account', 'saxon' ),
			esc_url( wp_logout_url( $here ) ),
			esc_html__( 'Log out', 'saxon' )
		);
		return;
	}

	printf(
		'<a class="button button--small button--outline" href="%1$s">%2$s</a>',
		esc_url( wp_login_url( $here ) ),
		esc_html__( 'Log in', 'saxon' )
	);
}
