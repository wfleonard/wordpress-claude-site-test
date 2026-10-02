<?php
/**
 * Fallback template: blog index, archives and anything without a more
 * specific template.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<header class="page-hero page-hero--compact">
	<div class="container">
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<h1 class="page-hero__title"><?php single_post_title(); ?></h1>
		<?php elseif ( is_search() ) : ?>
			<h1 class="page-hero__title">
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'Search results for "%s"', 'saxon' ), esc_html( get_search_query() ) );
				?>
			</h1>
		<?php elseif ( is_archive() ) : ?>
			<?php the_archive_title( '<h1 class="page-hero__title">', '</h1>' ); ?>
			<?php the_archive_description( '<div class="page-hero__lead">', '</div>' ); ?>
		<?php else : ?>
			<h1 class="page-hero__title"><?php esc_html_e( 'Latest news', 'saxon' ); ?></h1>
		<?php endif; ?>
	</div>
</header>

<div class="container section">
	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'card' );
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'mid_size'           => 1,
				'prev_text'          => __( 'Previous', 'saxon' ),
				'next_text'          => __( 'Next', 'saxon' ),
				'screen_reader_text' => __( 'Posts navigation', 'saxon' ),
			)
		);
		?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</div>

<?php
get_footer();
