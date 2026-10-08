/**
 * Saxon Contact Form: inline validation and submit without a page reload.
 *
 * Progressive enhancement only. Without JavaScript the form posts normally
 * and the server redirects back with the result. No jQuery.
 *
 * Author: William Leonard, Saxon Enterprises, Inc.
 */
( function () {
	'use strict';

	const text = window.saxonContactForm || {};

	/**
	 * Show or clear the error for one field.
	 *
	 * @param {HTMLElement} field   Input, textarea or checkbox.
	 * @param {string}      message Error text, empty to clear.
	 */
	function setError( field, message ) {
		const error = document.getElementById( field.id + '-error' );
		if ( message ) {
			field.setAttribute( 'aria-invalid', 'true' );
		} else {
			field.removeAttribute( 'aria-invalid' );
		}
		if ( error ) {
			error.textContent = message;
			error.hidden = ! message;
		}
	}

	/**
	 * Browser validity mapped to the plugin's own wording.
	 *
	 * @param {HTMLElement} field Field.
	 * @return {string} Error text or empty.
	 */
	function validityMessage( field ) {
		const v = field.validity;
		if ( v.valid ) {
			return '';
		}
		if ( v.valueMissing ) {
			return text.required;
		}
		if ( v.typeMismatch && 'email' === field.type ) {
			return text.email;
		}
		if ( v.tooLong ) {
			return text.tooLong;
		}
		return field.validationMessage;
	}

	/**
	 * Show the status box.
	 *
	 * @param {HTMLElement} status  Status element.
	 * @param {string}      kind    success, error or info.
	 * @param {string}      message Text.
	 * @param {boolean}     focus   Move focus to the box.
	 */
	function setStatus( status, kind, message, focus ) {
		status.className = 'saxon-cf__status saxon-cf__status--' + kind;
		status.textContent = message;
		status.hidden = false;
		if ( focus ) {
			status.focus();
		}
	}

	/**
	 * Wire up one form.
	 *
	 * @param {HTMLFormElement} form Form.
	 */
	function enhance( form ) {
		const wrapper = form.closest( '.saxon-cf' );
		const status = wrapper.querySelector( '[data-saxon-cf-status]' );
		const button = form.querySelector( '[type="submit"]' );
		const fields = Array.prototype.slice.call(
			form.querySelectorAll( '.saxon-cf__field input, .saxon-cf__field textarea' )
		);

		// Our own messages replace the browser's bubbles.
		form.noValidate = true;

		fields.forEach( function ( field ) {
			// Validate on leaving a field, then live once it has been flagged.
			field.addEventListener( 'blur', function () {
				if ( field.value || 'true' === field.getAttribute( 'aria-invalid' ) ) {
					setError( field, validityMessage( field ) );
				}
			} );
			field.addEventListener( 'input', function () {
				if ( 'true' === field.getAttribute( 'aria-invalid' ) ) {
					setError( field, validityMessage( field ) );
				}
			} );
			field.addEventListener( 'change', function () {
				if ( 'checkbox' === field.type ) {
					setError( field, validityMessage( field ) );
				}
			} );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			if ( 'true' === button.getAttribute( 'aria-busy' ) ) {
				return;
			}

			let firstInvalid = null;
			fields.forEach( function ( field ) {
				const message = validityMessage( field );
				setError( field, message );
				if ( message && ! firstInvalid ) {
					firstInvalid = field;
				}
			} );

			if ( firstInvalid ) {
				setStatus( status, 'error', text.fixErrors, false );
				firstInvalid.focus();
				return;
			}

			const data = new FormData( form );
			data.append( 'saxon_cf_ajax', '1' );

			button.setAttribute( 'aria-busy', 'true' );
			button.disabled = true;
			setStatus( status, 'info', text.sending, false );

			window
				// getAttribute, because form.action is shadowed by the hidden
				// input named "action" that admin-post.php needs.
				.fetch( form.getAttribute( 'action' ), {
					method: 'POST',
					body: data,
					credentials: 'same-origin',
					headers: { Accept: 'application/json' },
				} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					if ( json && json.success ) {
						form.reset();
						fields.forEach( function ( field ) {
							setError( field, '' );
						} );
						setStatus( status, 'success', json.data.message, true );
						return;
					}

					const errors = ( json && json.data && json.data.errors ) || {};
					let focusField = null;
					fields.forEach( function ( field ) {
						const message = errors[ field.name ] || '';
						setError( field, message );
						if ( message && ! focusField ) {
							focusField = field;
						}
					} );

					setStatus( status, 'error', errors._form || text.fixErrors, ! focusField );
					if ( focusField ) {
						focusField.focus();
					}
				} )
				.catch( function () {
					setStatus( status, 'error', text.failed, true );
				} )
				.then( function () {
					button.removeAttribute( 'aria-busy' );
					button.disabled = false;
				} );
		} );
	}

	function init() {
		document.querySelectorAll( 'form[data-saxon-cf]' ).forEach( enhance );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
