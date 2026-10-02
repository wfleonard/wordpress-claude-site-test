<?php
/**
 * Call to action band linking to the contact page.
 *
 * @package Saxon
 */

defined( 'ABSPATH' ) || exit;

$saxon_cta_url = saxon_resolve_link( saxon_option( 'hero_alt_url' ) );
if ( ! $saxon_cta_url || is_page_template( 'page-templates/contact.php' ) ) {
	return;
}
?>
<section class="cta-band" aria-labelledby="cta-title">
	<div class="container cta-band__inner">
		<div>
			<h2 id="cta-title" class="cta-band__title"><?php esc_html_e( 'Ready to start a conversation?', 'saxon' ); ?></h2>
			<p class="cta-band__text"><?php esc_html_e( 'Tell us what you are working on and we will get back to you within one business day.', 'saxon' ); ?></p>
		</div>
		<a class="button button--light" href="<?php echo esc_url( $saxon_cta_url ); ?>"><?php echo esc_html( saxon_option( 'hero_alt_label' ) ); ?></a>
	</div>
</section>
