<?php
/**
 * Field definitions and validation, shared by the form and the handler.
 *
 * @package SaxonContactForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Visible form fields.
 *
 * @return array[] Keyed by field name.
 */
function saxon_cf_fields() {
	$fields = array(
		'name'    => array(
			'label'        => __( 'Name', 'saxon-contact-form' ),
			'type'         => 'text',
			'required'     => true,
			'maxlength'    => 100,
			'autocomplete' => 'name',
		),
		'email'   => array(
			'label'        => __( 'Email', 'saxon-contact-form' ),
			'type'         => 'email',
			'required'     => true,
			'maxlength'    => 254,
			'autocomplete' => 'email',
		),
		'phone'   => array(
			'label'        => __( 'Phone', 'saxon-contact-form' ),
			'type'         => 'tel',
			'required'     => false,
			'maxlength'    => 40,
			'autocomplete' => 'tel',
		),
		'message' => array(
			'label'     => __( 'How can we help?', 'saxon-contact-form' ),
			'type'      => 'textarea',
			'required'  => true,
			'maxlength' => 5000,
		),
	);

	/**
	 * Filter the contact form fields.
	 *
	 * @param array[] $fields Field definitions keyed by name.
	 */
	return apply_filters( 'saxon_cf_fields', $fields );
}

/**
 * Sanitize and validate submitted values.
 *
 * @param array $raw Unslashed request data.
 * @return array { values: array, errors: array }
 */
function saxon_cf_validate( $raw ) {
	$values = array();
	$errors = array();

	foreach ( saxon_cf_fields() as $name => $field ) {
		$value = isset( $raw[ $name ] ) && is_scalar( $raw[ $name ] ) ? (string) $raw[ $name ] : '';
		$value = 'textarea' === $field['type'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );

		// Keep the typed text for refilling the form; it is only ever used
		// once the address below has passed is_email().
		$values[ $name ] = $value;

		if ( '' === $value ) {
			if ( ! empty( $field['required'] ) ) {
				$errors[ $name ] = __( 'This field is required.', 'saxon-contact-form' );
			}
			continue;
		}

		if ( mb_strlen( $value ) > $field['maxlength'] ) {
			/* translators: %d: maximum number of characters. */
			$errors[ $name ] = sprintf( __( 'Please keep this under %d characters.', 'saxon-contact-form' ), $field['maxlength'] );
		} elseif ( 'email' === $field['type'] && ( ! is_email( $value ) || sanitize_email( $value ) !== $value ) ) {
			$errors[ $name ] = __( 'Enter a valid email address, like name@example.com.', 'saxon-contact-form' );
		} elseif ( 'tel' === $field['type'] && ! preg_match( '/^[0-9+().\s-]{5,40}$/', $value ) ) {
			$errors[ $name ] = __( 'Enter a phone number using digits, spaces and + ( ) . - only.', 'saxon-contact-form' );
		}
	}

	if ( empty( $errors['message'] ) && preg_match_all( '#https?://#i', $values['message'] ?? '' ) > 3 ) {
		$errors['message'] = __( 'Please include no more than three links.', 'saxon-contact-form' );
	}

	if ( '' !== saxon_cf_option( 'consent' ) && empty( $raw['consent'] ) ) {
		$errors['consent'] = __( 'Please tick this box so we can store your details and reply.', 'saxon-contact-form' );
	}

	return array(
		'values' => $values,
		'errors' => $errors,
	);
}
