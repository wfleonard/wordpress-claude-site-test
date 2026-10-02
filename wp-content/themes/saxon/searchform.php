<?php
/**
 * Accessible search form.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

$saxon_search_id = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="search-form__label" for="<?php echo esc_attr( $saxon_search_id ); ?>"><?php esc_html_e( 'Search this site', 'saxon' ); ?></label>
	<div class="search-form__row">
		<input type="search" id="<?php echo esc_attr( $saxon_search_id ); ?>" class="search-form__field" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off">
		<button type="submit" class="button"><?php esc_html_e( 'Search', 'saxon' ); ?></button>
	</div>
</form>
