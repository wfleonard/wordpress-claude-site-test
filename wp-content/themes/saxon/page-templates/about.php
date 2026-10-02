<?php
/**
 * Template Name: About
 *
 * Wide content area for the company story, followed by a call to action.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry entry--about' ); ?>>
		<?php get_template_part( 'template-parts/page-hero' ); ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="container container--wide entry-thumbnail">
				<?php the_post_thumbnail( 'large' ); ?>
			</figure>
		<?php endif; ?>

		<div class="container entry-content entry-content--wide section">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_template_part( 'template-parts/cta' );

get_footer();
