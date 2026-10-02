<?php
/**
 * Per post SEO fields: title, description and noindex.
 *
 * @package SaxonSeoBasics
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types that get the SEO box: every public type except attachments.
 *
 * @return string[]
 */
function saxon_seo_post_types() {
	$types = get_post_types( array( 'public' => true ), 'names' );
	unset( $types['attachment'] );
	return array_values( $types );
}

/**
 * Register the meta keys so they are typed, sanitized and available to the
 * REST API for users who can edit the post.
 */
function saxon_seo_register_meta() {
	$keys = array(
		'_saxon_seo_title'       => array( 'string', 'sanitize_text_field' ),
		'_saxon_seo_description' => array( 'string', 'sanitize_textarea_field' ),
		'_saxon_seo_noindex'     => array( 'boolean', 'rest_sanitize_boolean' ),
	);

	foreach ( saxon_seo_post_types() as $type ) {
		foreach ( $keys as $key => $config ) {
			register_post_meta(
				$type,
				$key,
				array(
					'type'              => $config[0],
					'single'            => true,
					'sanitize_callback' => $config[1],
					'show_in_rest'      => true,
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}
}
add_action( 'init', 'saxon_seo_register_meta', 20 );

/**
 * Add the SEO box to the editor.
 */
function saxon_seo_add_meta_box() {
	add_meta_box( 'saxon-seo', __( 'Search and sharing', 'saxon-seo-basics' ), 'saxon_seo_render_meta_box', saxon_seo_post_types(), 'normal', 'low' );
}
add_action( 'add_meta_boxes', 'saxon_seo_add_meta_box' );

/**
 * Render the SEO box.
 *
 * @param WP_Post $post Post being edited.
 */
function saxon_seo_render_meta_box( $post ) {
	wp_nonce_field( 'saxon_seo_save_' . $post->ID, 'saxon_seo_nonce' );

	$title       = (string) get_post_meta( $post->ID, '_saxon_seo_title', true );
	$description = (string) get_post_meta( $post->ID, '_saxon_seo_description', true );
	$noindex     = (bool) get_post_meta( $post->ID, '_saxon_seo_noindex', true );
	?>
	<div class="saxon-seo-box">
		<p>
			<label for="saxon-seo-title"><?php esc_html_e( 'Search title', 'saxon-seo-basics' ); ?></label>
			<input type="text" class="widefat" id="saxon-seo-title" name="saxon_seo_title" value="<?php echo esc_attr( $title ); ?>" maxlength="120" data-saxon-seo-count="60" placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>" aria-describedby="saxon-seo-title-help">
			<span class="description" id="saxon-seo-title-help"><?php esc_html_e( 'Replaces the page title in search results and when shared. The site name is added after it. Empty uses the page title.', 'saxon-seo-basics' ); ?></span>
		</p>
		<p>
			<label for="saxon-seo-description"><?php esc_html_e( 'Meta description', 'saxon-seo-basics' ); ?></label>
			<textarea class="widefat" rows="3" id="saxon-seo-description" name="saxon_seo_description" maxlength="320" data-saxon-seo-count="160" aria-describedby="saxon-seo-description-help"><?php echo esc_textarea( $description ); ?></textarea>
			<span class="description" id="saxon-seo-description-help"><?php esc_html_e( 'The summary under the title in search results. Empty uses the excerpt, then the start of the content.', 'saxon-seo-basics' ); ?></span>
		</p>
		<p>
			<label class="saxon-seo-box__check">
				<input type="checkbox" name="saxon_seo_noindex" value="1" <?php checked( $noindex ); ?>>
				<?php esc_html_e( 'Hide from search engines (noindex) and leave out of the sitemap', 'saxon-seo-basics' ); ?>
			</label>
		</p>
	</div>
	<?php
}

/**
 * Save the SEO box.
 *
 * @param int $post_id Post ID.
 */
function saxon_seo_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['saxon_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['saxon_seo_nonce'] ) ), 'saxon_seo_save_' . $post_id ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$title       = isset( $_POST['saxon_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['saxon_seo_title'] ) ) : '';
	$description = isset( $_POST['saxon_seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['saxon_seo_description'] ) ) : '';
	$noindex     = ! empty( $_POST['saxon_seo_noindex'] );

	saxon_seo_update_or_delete( $post_id, '_saxon_seo_title', $title );
	saxon_seo_update_or_delete( $post_id, '_saxon_seo_description', $description );
	saxon_seo_update_or_delete( $post_id, '_saxon_seo_noindex', $noindex ? '1' : '' );
}
add_action( 'save_post', 'saxon_seo_save_meta_box' );

/**
 * Store a value, or delete the key when it is empty so the table stays lean.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param string $value   Value.
 */
function saxon_seo_update_or_delete( $post_id, $key, $value ) {
	if ( '' === $value ) {
		delete_post_meta( $post_id, $key );
	} else {
		update_post_meta( $post_id, $key, $value );
	}
}
