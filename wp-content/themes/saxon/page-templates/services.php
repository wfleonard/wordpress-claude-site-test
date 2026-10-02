<?php
/**
 * Template Name: Services
 *
 * Page content followed by a card for each child page (one per service).
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry entry--services' ); ?>>
		<?php get_template_part( 'template-parts/page-hero' ); ?>

		<div class="container entry-content entry-content--wide section">
			<?php the_content(); ?>
		</div>

		<?php
		$saxon_services = get_pages(
			array(
				'child_of'    => get_the_ID(),
				'parent'      => get_the_ID(),
				'sort_column' => 'menu_order,post_title',
			)
		);

		if ( $saxon_services ) :
			?>
			<section class="section section--tint" aria-labelledby="service-list-title">
				<div class="container">
					<h2 id="service-list-title" class="section__title"><?php esc_html_e( 'Service details', 'saxon' ); ?></h2>
					<div class="card-grid">
						<?php foreach ( $saxon_services as $saxon_service ) : ?>
							<article class="card">
								<div class="card__body">
									<h3 class="card__title"><a href="<?php echo esc_url( get_permalink( $saxon_service ) ); ?>"><?php echo esc_html( get_the_title( $saxon_service ) ); ?></a></h3>
									<div class="card__excerpt"><p><?php echo esc_html( get_the_excerpt( $saxon_service ) ); ?></p></div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_template_part( 'template-parts/cta' );

get_footer();
