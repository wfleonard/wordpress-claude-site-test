<?php
/**
 * Page title band. Uses the manual excerpt, when set, as the lead paragraph.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="page-hero">
	<div class="container">
		<?php the_title( '<h1 class="page-hero__title">', '</h1>' ); ?>
		<?php if ( has_excerpt() ) : ?>
			<p class="page-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
	</div>
</header>
