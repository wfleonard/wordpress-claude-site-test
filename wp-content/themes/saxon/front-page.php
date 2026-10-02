<?php
/**
 * Static home page: hero, editable page content, latest posts and a call to action.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();

$saxon_cta_url = saxon_resolve_link( saxon_option( 'hero_cta_url' ) );
$saxon_alt_url = saxon_resolve_link( saxon_option( 'hero_alt_url' ) );
?>

<section class="hero" aria-labelledby="hero-title">
	<div class="container hero__inner">
		<h1 id="hero-title" class="hero__title"><?php echo esc_html( saxon_option( 'hero_heading' ) ); ?></h1>
		<p class="hero__text"><?php echo esc_html( saxon_option( 'hero_text' ) ); ?></p>
		<?php if ( $saxon_cta_url || $saxon_alt_url ) : ?>
			<div class="hero__actions">
				<?php if ( $saxon_cta_url ) : ?>
					<a class="button button--light" href="<?php echo esc_url( $saxon_cta_url ); ?>"><?php echo esc_html( saxon_option( 'hero_cta_label' ) ); ?></a>
				<?php endif; ?>
				<?php if ( $saxon_alt_url ) : ?>
					<a class="button button--outline-light" href="<?php echo esc_url( $saxon_alt_url ); ?>"><?php echo esc_html( saxon_option( 'hero_alt_label' ) ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
if ( 'page' === get_option( 'show_on_front' ) ) :
	while ( have_posts() ) :
		the_post();
		if ( '' !== trim( get_the_content() ) ) :
			?>
			<div class="container entry-content entry-content--wide section">
				<?php the_content(); ?>
			</div>
			<?php
		endif;
	endwhile;
endif;

$saxon_latest = new WP_Query(
	array(
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( $saxon_latest->have_posts() ) :
	?>
	<section class="section section--tint" aria-labelledby="latest-title">
		<div class="container">
			<div class="section__header">
				<h2 id="latest-title" class="section__title"><?php esc_html_e( 'Latest news', 'saxon' ); ?></h2>
				<?php if ( get_option( 'page_for_posts' ) ) : ?>
					<a class="section__link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php esc_html_e( 'View all news', 'saxon' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="card-grid">
				<?php
				while ( $saxon_latest->have_posts() ) :
					$saxon_latest->the_post();
					get_template_part( 'template-parts/content', 'card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
	<?php
endif;

get_template_part( 'template-parts/cta' );

get_footer();
