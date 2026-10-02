<?php
/**
 * Not found template.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<header class="page-hero page-hero--compact">
	<div class="container container--narrow">
		<h1 class="page-hero__title"><?php esc_html_e( 'Page not found', 'saxon' ); ?></h1>
		<p class="page-hero__lead"><?php esc_html_e( 'The page you were looking for has moved or no longer exists. Try a search, or head back to the home page.', 'saxon' ); ?></p>
	</div>
</header>

<div class="container container--narrow section">
	<?php get_search_form(); ?>
	<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'saxon' ); ?></a></p>
</div>

<?php
get_footer();
