<?php
/**
 * Single post template.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
		<header class="page-hero page-hero--compact">
			<div class="container container--narrow">
				<?php the_title( '<h1 class="page-hero__title">', '</h1>' ); ?>
				<?php saxon_posted_on(); ?>
			</div>
		</header>

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

		<footer class="container container--narrow entry-footer">
			<?php
			$categories = get_the_category_list( ', ' );
			$post_tags  = get_the_tag_list( '', ', ' );
			if ( $categories ) {
				/* translators: %s: category list. */
				printf( '<p class="entry-terms">' . esc_html__( 'Filed under %s', 'saxon' ) . '</p>', $categories ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( $post_tags ) {
				/* translators: %s: tag list. */
				printf( '<p class="entry-terms">' . esc_html__( 'Tagged %s', 'saxon' ) . '</p>', $post_tags ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			the_post_navigation(
				array(
					'prev_text' => '<span class="nav-label">' . esc_html__( 'Previous', 'saxon' ) . '</span> %title',
					'next_text' => '<span class="nav-label">' . esc_html__( 'Next', 'saxon' ) . '</span> %title',
				)
			);
			?>
		</footer>

		<?php
		if ( comments_open() || get_comments_number() ) {
			echo '<div class="container container--narrow">';
			comments_template();
			echo '</div>';
		}
		?>
	</article>
	<?php
endwhile;

get_footer();
