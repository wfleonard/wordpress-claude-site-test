<?php
/**
 * Shown when a query returns nothing.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="no-results">
	<h2><?php esc_html_e( 'Nothing found', 'saxon' ); ?></h2>
	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'No results matched your search. Try different keywords.', 'saxon' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'There is nothing here yet. Try searching instead.', 'saxon' ); ?></p>
	<?php endif; ?>
	<?php get_search_form(); ?>
</section>
