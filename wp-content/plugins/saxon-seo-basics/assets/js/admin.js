/**
 * Saxon SEO Basics admin: character counters and the share image picker.
 * No jQuery.
 *
 * Author: William Leonard, Saxon Enterprises, Inc.
 */
( function () {
	'use strict';

	const text = window.saxonSeoAdmin || {};

	/**
	 * Live "n of about max characters" note under a field.
	 *
	 * @param {HTMLInputElement|HTMLTextAreaElement} field Field with data-saxon-seo-count.
	 */
	function addCounter( field ) {
		const max = parseInt( field.getAttribute( 'data-saxon-seo-count' ), 10 );
		const note = document.createElement( 'span' );
		note.className = 'saxon-seo-count';
		note.id = field.id + '-count';
		note.setAttribute( 'aria-live', 'polite' );
		field.insertAdjacentElement( 'afterend', note );

		const described = field.getAttribute( 'aria-describedby' );
		field.setAttribute( 'aria-describedby', ( described ? described + ' ' : '' ) + note.id );

		function update() {
			const length = field.value.length;
			note.textContent = ( text.counter || '%1$d / %2$d' ).replace( '%1$d', length ).replace( '%2$d', max );
			note.classList.toggle( 'saxon-seo-count--over', length > max );
		}

		field.addEventListener( 'input', update );
		update();
	}

	/**
	 * Media library picker for the default share image.
	 *
	 * @param {HTMLElement} box Wrapper with data-saxon-seo-image.
	 */
	function addImagePicker( box ) {
		const input = box.querySelector( 'input[type="hidden"]' );
		const preview = box.querySelector( 'img' );
		const choose = box.querySelector( '[data-saxon-seo-image-choose]' );
		const remove = box.querySelector( '[data-saxon-seo-image-remove]' );
		let frame = null;

		if ( ! window.wp || ! window.wp.media ) {
			choose.hidden = true;
			return;
		}

		choose.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = window.wp.media( {
					title: text.chooseTitle,
					button: { text: text.chooseLabel },
					library: { type: 'image' },
					multiple: false,
				} );
				frame.on( 'select', function () {
					const image = frame.state().get( 'selection' ).first().toJSON();
					const size = ( image.sizes && ( image.sizes.medium || image.sizes.full ) ) || image;
					input.value = image.id;
					preview.src = size.url;
					preview.hidden = false;
					remove.hidden = false;
				} );
			}
			frame.open();
		} );

		remove.addEventListener( 'click', function () {
			input.value = '';
			preview.removeAttribute( 'src' );
			preview.hidden = true;
			remove.hidden = true;
			choose.focus();
		} );
	}

	function init() {
		document.querySelectorAll( '[data-saxon-seo-count]' ).forEach( addCounter );
		document.querySelectorAll( '[data-saxon-seo-image]' ).forEach( addImagePicker );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
