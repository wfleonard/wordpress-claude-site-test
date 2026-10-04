<?php
/**
 * Template Name: Wide
 *
 * Page with a wide content area for block layouts such as pricing tables,
 * card grids and columns. Text blocks keep a readable line length.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
		<?php get_template_part( 'template-parts/page-hero' ); ?>
		<div class="container entry-content entry-content--wide">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
