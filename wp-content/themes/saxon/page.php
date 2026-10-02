<?php
/**
 * Default page template.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/content', 'page' );
endwhile;

get_footer();
