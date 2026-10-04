<?php
/**
 * Site header.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'saxon' ); ?></a>

<header class="site-header">
	<div class="container site-header__inner">
		<div class="site-branding">
			<?php saxon_site_branding(); ?>
		</div>

		<?php if ( has_nav_menu( 'primary' ) || get_pages( array( 'number' => 1 ) ) ) : ?>
			<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'saxon' ); ?>">
				<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-menu">
					<span class="menu-toggle__icon" aria-hidden="true"><span></span></span>
					<span class="menu-toggle__label"><?php esc_html_e( 'Menu', 'saxon' ); ?></span>
				</button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_id'        => 'primary-menu',
						'menu_class'     => 'menu primary-menu',
						'container'      => false,
						'depth'          => 2,
						'fallback_cb'    => 'saxon_menu_fallback',
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<div class="header-account">
			<?php saxon_header_account(); ?>
		</div>
	</div>
</header>

<main id="main" class="site-main" tabindex="-1">
