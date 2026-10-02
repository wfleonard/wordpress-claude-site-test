<?php
/**
 * "Connected sign in accounts" section on the user's own profile screen.
 *
 * @package SaxonSocialLogin
 * @author  William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the section.
 *
 * @param WP_User $user Profile being edited (always the current user on show_user_profile).
 */
function saxon_sso_profile_section( $user ) {
	$providers = saxon_sso_providers();
	if ( ! $providers ) {
		return;
	}
	?>
	<h2 id="saxon-sso"><?php esc_html_e( 'Connected sign in accounts', 'saxon-social-login' ); ?></h2>
	<?php
	if ( isset( $_GET['saxon_sso_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-error inline"><p>' . esc_html( saxon_sso_message( sanitize_key( wp_unslash( $_GET['saxon_sso_error'] ) ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
	} elseif ( isset( $_GET['saxon_sso'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$done = sanitize_key( wp_unslash( $_GET['saxon_sso'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success inline"><p>' . esc_html( 'linked' === $done ? __( 'Account connected. You can now sign in with it.', 'saxon-social-login' ) : __( 'Account disconnected.', 'saxon-social-login' ) ) . '</p></div>';
	}
	?>
	<p><?php esc_html_e( 'Connect a Google or Microsoft account to sign in without your password. Your username and password keep working.', 'saxon-social-login' ); ?></p>
	<table class="form-table" role="presentation">
		<?php foreach ( $providers as $slug => $provider ) : ?>
			<?php $linked = (bool) get_user_meta( $user->ID, saxon_sso_meta_key( $slug ), true ); ?>
			<tr>
				<th scope="row"><?php echo esc_html( $provider['label'] ); ?></th>
				<td>
					<?php if ( $linked ) : ?>
						<span><?php esc_html_e( 'Connected', 'saxon-social-login' ); ?></span>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=saxon_sso_unlink&provider=' . $slug ), 'saxon_sso_unlink_' . $slug ) ); ?>"><?php esc_html_e( 'Disconnect', 'saxon-social-login' ); ?></a>
					<?php else : ?>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'link', '1', saxon_sso_start_url( $slug ) ), 'saxon_sso_link_' . $slug ) ); ?>">
							<?php
							/* translators: %s: Google or Microsoft. */
							echo esc_html( sprintf( __( 'Connect %s account', 'saxon-social-login' ), $provider['label'] ) );
							?>
						</a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'saxon_sso_profile_section' );

/**
 * Disconnect a provider from the current user.
 */
function saxon_sso_unlink() {
	$provider = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
	check_admin_referer( 'saxon_sso_unlink_' . $provider );
	delete_user_meta( get_current_user_id(), saxon_sso_meta_key( $provider ) );
	wp_safe_redirect( add_query_arg( 'saxon_sso', 'unlinked', admin_url( 'profile.php' ) ) . '#saxon-sso' );
	exit;
}
add_action( 'admin_post_saxon_sso_unlink', 'saxon_sso_unlink' );
