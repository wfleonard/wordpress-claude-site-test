<?php
/**
 * Page body used by the default page template.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
	<?php get_template_part( 'template-parts/page-hero' ); ?>

	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="container container--wide entry-thumbnail">
			<?php the_post_thumbnail( 'large' ); ?>
		</figure>
	<?php endif; ?>

	<div class="container container--narrow entry-content">
		<?php
		the_content();
		wp_link_pages(
			array(
				'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'saxon' ) . '">',
				'after'  => '</nav>',
			)
		);
		?>
	</div>
</article>
