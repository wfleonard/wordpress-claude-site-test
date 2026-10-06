<?php
/**
 * Site footer.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<footer class="site-footer">
	<div class="container site-footer__inner">
		<div class="site-footer__brand">
			<p class="site-footer__name"><?php bloginfo( 'name' ); ?></p>
			<?php saxon_contact_details( 'contact-details--footer' ); ?>
		</div>

		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer', 'saxon' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'menu_class'     => 'menu footer-menu',
						'container'      => false,
						'depth'          => 1,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
			<div class="site-footer__widgets">
				<?php dynamic_sidebar( 'footer-1' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="container site-footer__legal">
		<p>
			<?php
			printf(
				/* translators: 1: year, 2: company name. */
				esc_html__( '&copy; %1$s %2$s. All rights reserved.', 'saxon' ),
				esc_html( wp_date( 'Y' ) ),
				'Saxon Enterprises, Inc.'
			);
			?>
		</p>
		<p>
			<?php
			printf(
				/* translators: %s: link to saxonenterprises.net. */
				esc_html__( 'A Saxon Enterprises site. Projects, pricing and contact: %s', 'saxon' ),
				'<a href="https://saxonenterprises.net/">saxonenterprises.net</a>'
			);
			?>
		</p>
		<?php
		if ( function_exists( 'the_privacy_policy_link' ) ) {
			the_privacy_policy_link( '<p>', '</p>' );
		}
		?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
