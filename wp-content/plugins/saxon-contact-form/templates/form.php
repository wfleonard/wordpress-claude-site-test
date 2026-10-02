<?php
/**
 * Contact form markup.
 *
 * Variables from saxon_cf_render_form(): $id, $state, $consent.
 *
 * @package SaxonContactForm
 */

defined( 'ABSPATH' ) || exit;

$saxon_cf_errors = $state['errors'];
$saxon_cf_values = $state['values'];
$saxon_cf_sent   = 'sent' === $state['status'];
?>
<div class="saxon-cf" id="<?php echo esc_attr( $id ); ?>">
	<div class="saxon-cf__status<?php echo $saxon_cf_sent ? ' saxon-cf__status--success' : ( $saxon_cf_errors ? ' saxon-cf__status--error' : '' ); ?>" role="status" aria-live="polite" tabindex="-1" data-saxon-cf-status<?php echo ( $saxon_cf_sent || $saxon_cf_errors ) ? '' : ' hidden'; ?>>
		<?php
		if ( $saxon_cf_sent ) {
			echo esc_html( saxon_cf_option( 'success' ) );
		} elseif ( isset( $saxon_cf_errors['_form'] ) ) {
			echo esc_html( $saxon_cf_errors['_form'] );
		} elseif ( $saxon_cf_errors ) {
			esc_html_e( 'Please fix the highlighted fields and try again.', 'saxon-contact-form' );
		}
		?>
	</div>

	<form class="saxon-cf__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-saxon-cf>
		<input type="hidden" name="action" value="saxon_contact">
		<input type="hidden" name="saxon_cf_return" value="<?php echo esc_attr( $id ); ?>">
		<input type="hidden" name="saxon_cf_ts" value="<?php echo esc_attr( saxon_cf_time_token() ); ?>">
		<?php wp_nonce_field( 'saxon_contact', 'saxon_cf_nonce' ); ?>

		<p class="saxon-cf__required-note"><?php esc_html_e( 'Fields marked * are required.', 'saxon-contact-form' ); ?></p>

		<?php
		foreach ( saxon_cf_fields() as $saxon_cf_name => $saxon_cf_field ) :
			$saxon_cf_input_id = $id . '-' . $saxon_cf_name;
			$saxon_cf_error    = $saxon_cf_errors[ $saxon_cf_name ] ?? '';
			$saxon_cf_value    = $saxon_cf_values[ $saxon_cf_name ] ?? '';
			$saxon_cf_attrs    = sprintf(
				'id="%1$s" name="%2$s" maxlength="%3$d" aria-describedby="%1$s-error"%4$s%5$s%6$s',
				esc_attr( $saxon_cf_input_id ),
				esc_attr( $saxon_cf_name ),
				(int) $saxon_cf_field['maxlength'],
				empty( $saxon_cf_field['required'] ) ? '' : ' required aria-required="true"',
				empty( $saxon_cf_field['autocomplete'] ) ? '' : ' autocomplete="' . esc_attr( $saxon_cf_field['autocomplete'] ) . '"',
				$saxon_cf_error ? ' aria-invalid="true"' : ''
			);
			?>
			<div class="saxon-cf__field saxon-cf__field--<?php echo esc_attr( $saxon_cf_name ); ?>">
				<label for="<?php echo esc_attr( $saxon_cf_input_id ); ?>">
					<?php echo esc_html( $saxon_cf_field['label'] ); ?>
					<?php if ( ! empty( $saxon_cf_field['required'] ) ) : ?>
						<span class="saxon-cf__required" aria-hidden="true">*</span>
					<?php else : ?>
						<span class="saxon-cf__optional"><?php esc_html_e( '(optional)', 'saxon-contact-form' ); ?></span>
					<?php endif; ?>
				</label>
				<?php if ( 'textarea' === $saxon_cf_field['type'] ) : ?>
					<textarea <?php echo $saxon_cf_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> rows="6"><?php echo esc_textarea( $saxon_cf_value ); ?></textarea>
				<?php else : ?>
					<input type="<?php echo esc_attr( $saxon_cf_field['type'] ); ?>" <?php echo $saxon_cf_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?> value="<?php echo esc_attr( $saxon_cf_value ); ?>">
				<?php endif; ?>
				<p class="saxon-cf__error" id="<?php echo esc_attr( $saxon_cf_input_id ); ?>-error"<?php echo $saxon_cf_error ? '' : ' hidden'; ?>><?php echo esc_html( $saxon_cf_error ); ?></p>
			</div>
		<?php endforeach; ?>

		<?php if ( '' !== $consent ) : ?>
			<?php $saxon_cf_error = $saxon_cf_errors['consent'] ?? ''; ?>
			<div class="saxon-cf__field saxon-cf__field--consent">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>-consent" name="consent" value="1" required aria-required="true" aria-describedby="<?php echo esc_attr( $id ); ?>-consent-error"<?php echo $saxon_cf_error ? ' aria-invalid="true"' : ''; ?><?php checked( ! empty( $saxon_cf_values ) && ! $saxon_cf_error ); ?>>
				<label for="<?php echo esc_attr( $id ); ?>-consent"><?php echo saxon_cf_consent_label( $consent ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?> <span class="saxon-cf__required" aria-hidden="true">*</span></label>
				<p class="saxon-cf__error" id="<?php echo esc_attr( $id ); ?>-consent-error"<?php echo $saxon_cf_error ? '' : ' hidden'; ?>><?php echo esc_html( $saxon_cf_error ); ?></p>
			</div>
		<?php endif; ?>

		<div class="saxon-cf__trap" aria-hidden="true">
			<label for="<?php echo esc_attr( $id ); ?>-website"><?php esc_html_e( 'Leave this field empty', 'saxon-contact-form' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $id ); ?>-website" name="website" value="" tabindex="-1" autocomplete="off">
		</div>

		<div class="saxon-cf__actions">
			<button type="submit" class="button saxon-cf__submit"><?php esc_html_e( 'Send message', 'saxon-contact-form' ); ?></button>
		</div>
	</form>
</div>
