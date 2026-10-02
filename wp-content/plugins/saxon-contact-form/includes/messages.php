<?php
/**
 * Stored messages: a private post type, its admin screens and the
 * WordPress privacy export and erase hooks.
 *
 * @package SaxonContactForm
 */

defined( 'ABSPATH' ) || exit;

const SAXON_CF_POST_TYPE = 'saxon_cf_message';

/**
 * Register the private post type. Messages are never public or queryable.
 */
function saxon_cf_register_post_type() {
	register_post_type(
		SAXON_CF_POST_TYPE,
		array(
			'labels'          => array(
				'name'          => __( 'Messages', 'saxon-contact-form' ),
				'singular_name' => __( 'Message', 'saxon-contact-form' ),
				'all_items'     => __( 'All messages', 'saxon-contact-form' ),
				'edit_item'     => __( 'Message', 'saxon-contact-form' ),
				'search_items'  => __( 'Search messages', 'saxon-contact-form' ),
				'not_found'     => __( 'No messages yet.', 'saxon-contact-form' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 26,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'rewrite'         => false,
			'query_var'       => false,
		)
	);
}
add_action( 'init', 'saxon_cf_register_post_type' );

/**
 * Restrict the Messages screens to people who can manage options; the
 * messages hold visitors' personal data.
 *
 * @param string[] $caps    Primitive caps.
 * @param string   $cap     Requested cap.
 * @param int      $user_id User.
 * @param array    $args    Extra args.
 * @return string[]
 */
function saxon_cf_restrict_caps( $caps, $cap, $user_id, $args ) {
	$post_caps = array( 'edit_post', 'read_post', 'delete_post' );
	if ( in_array( $cap, $post_caps, true ) && ! empty( $args[0] ) && SAXON_CF_POST_TYPE === get_post_type( $args[0] ) ) {
		$caps[] = 'manage_options';
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'saxon_cf_restrict_caps', 10, 4 );

/**
 * Hide the menu from users who cannot manage options.
 */
function saxon_cf_hide_menu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_menu_page( 'edit.php?post_type=' . SAXON_CF_POST_TYPE );
	}
}
add_action( 'admin_menu', 'saxon_cf_hide_menu', 99 );

/**
 * Block direct list screen access for users who cannot manage options.
 */
function saxon_cf_guard_list_screen() {
	$screen = get_current_screen();
	if ( $screen && SAXON_CF_POST_TYPE === $screen->post_type && ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to view messages.', 'saxon-contact-form' ), 403 );
	}
}
add_action( 'current_screen', 'saxon_cf_guard_list_screen' );

/**
 * Save a message.
 *
 * @param array $values  Sanitized values.
 * @param int   $page_id Source page.
 * @param bool  $sent    Whether the email went out.
 * @return int Post ID or 0.
 */
function saxon_cf_store_message( $values, $page_id, $sent ) {
	$id = wp_insert_post(
		array(
			'post_type'    => SAXON_CF_POST_TYPE,
			'post_status'  => 'private',
			'post_title'   => sprintf( '%1$s (%2$s)', $values['name'], $values['email'] ),
			'post_content' => '',
			'meta_input'   => array(
				'_saxon_cf_values'    => $values,
				'_saxon_cf_email'     => $values['email'],
				'_saxon_cf_page'      => (int) $page_id,
				'_saxon_cf_mail_sent' => $sent ? 1 : 0,
			),
		),
		true
	);
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Read only details box on the message screen.
 */
function saxon_cf_add_meta_box() {
	add_meta_box( 'saxon-cf-details', __( 'Message', 'saxon-contact-form' ), 'saxon_cf_render_meta_box', SAXON_CF_POST_TYPE, 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'saxon_cf_add_meta_box' );

/**
 * Render the details box.
 *
 * @param WP_Post $post Message.
 */
function saxon_cf_render_meta_box( $post ) {
	$values = (array) get_post_meta( $post->ID, '_saxon_cf_values', true );
	$page   = (int) get_post_meta( $post->ID, '_saxon_cf_page', true );
	$sent   = (int) get_post_meta( $post->ID, '_saxon_cf_mail_sent', true );

	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( saxon_cf_fields() as $name => $field ) {
		if ( ! isset( $values[ $name ] ) || '' === $values[ $name ] ) {
			continue;
		}
		$value = 'email' === $name
			? sprintf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $values[ $name ] ), esc_html( $values[ $name ] ) )
			: nl2br( esc_html( $values[ $name ] ) );
		printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $field['label'] ), $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}
	if ( $page ) {
		printf( '<tr><th scope="row">%1$s</th><td><a href="%2$s">%3$s</a></td></tr>', esc_html__( 'Sent from', 'saxon-contact-form' ), esc_url( get_permalink( $page ) ), esc_html( get_the_title( $page ) ) );
	}
	printf(
		'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
		esc_html__( 'Email delivery', 'saxon-contact-form' ),
		$sent ? esc_html__( 'Sent', 'saxon-contact-form' ) : '<strong>' . esc_html__( 'Failed: check the site\'s mail setup', 'saxon-contact-form' ) . '</strong>'
	);
	echo '</tbody></table>';
}

/**
 * List screen columns.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function saxon_cf_columns( $columns ) {
	return array(
		'cb'               => $columns['cb'],
		'title'            => __( 'From', 'saxon-contact-form' ),
		'saxon_cf_excerpt' => __( 'Message', 'saxon-contact-form' ),
		'saxon_cf_sent'    => __( 'Email', 'saxon-contact-form' ),
		'date'             => $columns['date'],
	);
}
add_filter( 'manage_' . SAXON_CF_POST_TYPE . '_posts_columns', 'saxon_cf_columns' );

/**
 * List screen column values.
 *
 * @param string $column  Column.
 * @param int    $post_id Message.
 */
function saxon_cf_column_value( $column, $post_id ) {
	if ( 'saxon_cf_excerpt' === $column ) {
		$values = (array) get_post_meta( $post_id, '_saxon_cf_values', true );
		echo esc_html( wp_trim_words( $values['message'] ?? '', 16 ) );
	} elseif ( 'saxon_cf_sent' === $column ) {
		echo get_post_meta( $post_id, '_saxon_cf_mail_sent', true ) ? esc_html__( 'Sent', 'saxon-contact-form' ) : '<strong>' . esc_html__( 'Failed', 'saxon-contact-form' ) . '</strong>';
	}
}
add_action( 'manage_' . SAXON_CF_POST_TYPE . '_posts_custom_column', 'saxon_cf_column_value', 10, 2 );

/**
 * Register the personal data exporter and eraser.
 *
 * @param array $items Registered items.
 * @return array
 */
function saxon_cf_register_exporter( $items ) {
	$items['saxon-contact-form'] = array(
		'exporter_friendly_name' => __( 'Contact form messages', 'saxon-contact-form' ),
		'callback'               => 'saxon_cf_export_personal_data',
	);
	return $items;
}
add_filter( 'wp_privacy_personal_data_exporters', 'saxon_cf_register_exporter' );

/**
 * Register the eraser.
 *
 * @param array $items Registered items.
 * @return array
 */
function saxon_cf_register_eraser( $items ) {
	$items['saxon-contact-form'] = array(
		'eraser_friendly_name' => __( 'Contact form messages', 'saxon-contact-form' ),
		'callback'             => 'saxon_cf_erase_personal_data',
	);
	return $items;
}
add_filter( 'wp_privacy_personal_data_erasers', 'saxon_cf_register_eraser' );

/**
 * Messages sent from an email address, one page at a time.
 *
 * @param string $email Address.
 * @param int    $page  Page number.
 * @return int[]
 */
function saxon_cf_messages_for_email( $email, $page ) {
	return get_posts(
		array(
			'post_type'      => SAXON_CF_POST_TYPE,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 50,
			'paged'          => max( 1, (int) $page ),
			'meta_key'       => '_saxon_cf_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'meta_value'     => sanitize_email( $email ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
}

/**
 * Exporter callback.
 *
 * @param string $email Address.
 * @param int    $page  Page.
 * @return array
 */
function saxon_cf_export_personal_data( $email, $page = 1 ) {
	$ids   = saxon_cf_messages_for_email( $email, $page );
	$items = array();

	foreach ( $ids as $id ) {
		$values = (array) get_post_meta( $id, '_saxon_cf_values', true );
		$data   = array(
			array(
				'name'  => __( 'Date', 'saxon-contact-form' ),
				'value' => get_the_date( '', $id ),
			),
		);
		foreach ( saxon_cf_fields() as $name => $field ) {
			if ( isset( $values[ $name ] ) && '' !== $values[ $name ] ) {
				$data[] = array(
					'name'  => $field['label'],
					'value' => $values[ $name ],
				);
			}
		}
		$items[] = array(
			'group_id'    => 'saxon-contact-form',
			'group_label' => __( 'Contact form messages', 'saxon-contact-form' ),
			'item_id'     => 'saxon-cf-' . $id,
			'data'        => $data,
		);
	}

	return array(
		'data' => $items,
		'done' => count( $ids ) < 50,
	);
}

/**
 * Eraser callback: deletes the messages outright.
 *
 * @param string $email Address.
 * @param int    $page  Page (always 1, since each pass deletes what it finds).
 * @return array
 */
function saxon_cf_erase_personal_data( $email, $page = 1 ) {
	unset( $page );
	$ids     = saxon_cf_messages_for_email( $email, 1 );
	$removed = false;

	foreach ( $ids as $id ) {
		$removed = (bool) wp_delete_post( $id, true ) || $removed;
	}

	return array(
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => count( $ids ) < 50,
	);
}

/**
 * Suggested privacy policy text.
 */
function saxon_cf_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}
	wp_add_privacy_policy_content(
		__( 'Saxon Contact Form', 'saxon-contact-form' ),
		wp_kses_post(
			'<p>' . __( 'When you use our contact form we collect your name, email address, optional phone number and your message, and email them to our team so we can reply. If message storage is turned on, a copy is kept in our website dashboard until it is deleted. Your IP address is not stored; a one way hash of it is kept for ten minutes to limit repeated submissions.', 'saxon-contact-form' ) . '</p>'
		)
	);
}
add_action( 'admin_init', 'saxon_cf_privacy_policy_content' );

/**
 * Messages are a record of what was sent, so drop Quick Edit.
 *
 * @param string[] $actions Row actions.
 * @param WP_Post  $post    Message.
 * @return string[]
 */
function saxon_cf_row_actions( $actions, $post ) {
	if ( SAXON_CF_POST_TYPE === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
	}
	return $actions;
}
add_filter( 'post_row_actions', 'saxon_cf_row_actions', 10, 2 );
