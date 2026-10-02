<?php
/**
 * Template Name: Contact
 *
 * Two columns: the page content (where a contact form block or shortcode
 * goes) and the business contact details from the Customizer.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry entry--contact' ); ?>>
		<?php get_template_part( 'template-parts/page-hero' ); ?>

		<div class="container section contact-layout">
			<div class="entry-content contact-layout__main">
				<?php the_content(); ?>
			</div>

			<aside class="contact-layout__aside" aria-labelledby="contact-aside-title">
				<h2 id="contact-aside-title" class="contact-layout__title"><?php esc_html_e( 'Get in touch', 'saxon' ); ?></h2>
				<?php saxon_contact_details( 'contact-details--card' ); ?>
			</aside>
		</div>
	</article>
	<?php
endwhile;

get_footer();
