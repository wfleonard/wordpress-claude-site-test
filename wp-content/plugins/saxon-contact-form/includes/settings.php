<?php
/**
 * Settings, Contact form screen.
 *
 * @package SaxonContactForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default option values.
 *
 * @return array
 */
function saxon_cf_defaults() {
	return array(
		'recipients'     => '',
		'subject_prefix' => '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ']',
		'success'        => __( 'Thank you. Your message has been sent and we will reply soon.', 'saxon-contact-form' ),
		'consent'        => __( 'I agree that my details are stored so you can reply to my enquiry.', 'saxon-contact-form' ),
		'store'          => 1,
	);
}

/**
 * One option value, falling back to the default.
 *
 * @param string $key Option key.
 * @return mixed
 */
function saxon_cf_option( $key ) {
	$options  = get_option( 'saxon_cf_options', array() );
	$defaults = saxon_cf_defaults();
	return is_array( $options ) && array_key_exists( $key, $options ) ? $options[ $key ] : ( $defaults[ $key ] ?? '' );
}

/**
 * Valid recipient addresses, falling back to the site admin email.
 *
 * @return string[]
 */
function saxon_cf_recipients() {
	$list = array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', (string) saxon_cf_option( 'recipients' ) ) ), 'is_email' );
	return $list ? array_values( $list ) : array( get_option( 'admin_email' ) );
}

/**
 * Register the option and its fields.
 */
function saxon_cf_register_settings() {
	register_setting(
		'saxon_cf',
		'saxon_cf_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'saxon_cf_sanitize_options',
			'default'           => array(),
		)
	);

	add_settings_section( 'saxon_cf_main', '', '__return_false', 'saxon-contact-form' );

	$fields = array(
		'recipients'     => __( 'Send messages to', 'saxon-contact-form' ),
		'subject_prefix' => __( 'Email subject prefix', 'saxon-contact-form' ),
		'success'        => __( 'Success message', 'saxon-contact-form' ),
		'consent'        => __( 'Consent checkbox text', 'saxon-contact-form' ),
		'store'          => __( 'Keep a copy', 'saxon-contact-form' ),
	);
	foreach ( $fields as $key => $label ) {
		add_settings_field(
			'saxon_cf_' . $key,
			$label,
			'saxon_cf_field_' . $key,
			'saxon-contact-form',
			'saxon_cf_main',
			array( 'label_for' => 'saxon_cf_' . $key )
		);
	}
}
add_action( 'admin_init', 'saxon_cf_register_settings' );

/**
 * Sanitize the saved options.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function saxon_cf_sanitize_options( $input ) {
	$input = is_array( $input ) ? $input : array();

	$emails = array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', (string) ( $input['recipients'] ?? '' ) ) ), 'is_email' );

	return array(
		'recipients'     => implode( ', ', $emails ),
		'subject_prefix' => sanitize_text_field( $input['subject_prefix'] ?? '' ),
		'success'        => sanitize_text_field( $input['success'] ?? '' ),
		'consent'        => sanitize_text_field( $input['consent'] ?? '' ),
		'store'          => empty( $input['store'] ) ? 0 : 1,
	);
}

/**
 * Add the settings page.
 */
function saxon_cf_admin_menu() {
	add_options_page(
		__( 'Contact form', 'saxon-contact-form' ),
		__( 'Contact form', 'saxon-contact-form' ),
		'manage_options',
		'saxon-contact-form',
		'saxon_cf_settings_page'
	);
}
add_action( 'admin_menu', 'saxon_cf_admin_menu' );

/**
 * Settings page markup.
 */
function saxon_cf_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'Add the form to any page with the Contact form block or the [saxon_contact_form] shortcode.', 'saxon-contact-form' ); ?></p>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'saxon_cf' );
			do_settings_sections( 'saxon-contact-form' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Recipients field.
 */
function saxon_cf_field_recipients() {
	printf(
		'<input type="text" class="regular-text" id="saxon_cf_recipients" name="saxon_cf_options[recipients]" value="%1$s" placeholder="%2$s" aria-describedby="saxon_cf_recipients_help"><p class="description" id="saxon_cf_recipients_help">%3$s</p>',
		esc_attr( saxon_cf_option( 'recipients' ) ),
		esc_attr( get_option( 'admin_email' ) ),
		esc_html__( 'One or more addresses separated by commas. Leave empty to use the site admin email.', 'saxon-contact-form' )
	);
}

/**
 * Subject prefix field.
 */
function saxon_cf_field_subject_prefix() {
	printf(
		'<input type="text" class="regular-text" id="saxon_cf_subject_prefix" name="saxon_cf_options[subject_prefix]" value="%s">',
		esc_attr( saxon_cf_option( 'subject_prefix' ) )
	);
}

/**
 * Success message field.
 */
function saxon_cf_field_success() {
	printf(
		'<input type="text" class="large-text" id="saxon_cf_success" name="saxon_cf_options[success]" value="%s">',
		esc_attr( saxon_cf_option( 'success' ) )
	);
}

/**
 * Consent text field.
 */
function saxon_cf_field_consent() {
	printf(
		'<input type="text" class="large-text" id="saxon_cf_consent" name="saxon_cf_options[consent]" value="%1$s" aria-describedby="saxon_cf_consent_help"><p class="description" id="saxon_cf_consent_help">%2$s</p>',
		esc_attr( saxon_cf_option( 'consent' ) ),
		esc_html__( 'Shown as a required checkbox, with a link to the privacy policy page when one is set. Leave empty to hide the checkbox.', 'saxon-contact-form' )
	);
}

/**
 * Store messages field.
 */
function saxon_cf_field_store() {
	printf(
		'<label><input type="checkbox" id="saxon_cf_store" name="saxon_cf_options[store]" value="1" %1$s> %2$s</label><p class="description">%3$s</p>',
		checked( (int) saxon_cf_option( 'store' ), 1, false ),
		esc_html__( 'Save each message under Messages in the dashboard', 'saxon-contact-form' ),
		esc_html__( 'A safety net in case an email is lost. Messages are included in the WordPress personal data export and erase tools.', 'saxon-contact-form' )
	);
}
