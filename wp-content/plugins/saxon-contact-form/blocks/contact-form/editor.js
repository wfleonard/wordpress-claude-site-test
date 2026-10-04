/**
 * Editor registration for the Contact form block. Plain JavaScript using the
 * globals WordPress provides, so no build step is needed.
 *
 * Author: William Leonard, Saxon Enterprises, Inc.
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const __ = wp.i18n.__;
	const useBlockProps = wp.blockEditor.useBlockProps;
	const ServerSideRender = wp.serverSideRender;
	const Disabled = wp.components.Disabled;

	wp.blocks.registerBlockType( 'saxon/contact-form', {
		edit: function Edit() {
			return el(
				'div',
				useBlockProps(),
				el( Disabled, null, el( ServerSideRender, { block: 'saxon/contact-form' } ) ),
				el(
					'p',
					{ className: 'saxon-cf__editor-note' },
					__( 'Set the recipient and messages under Settings, Contact form.', 'saxon-contact-form' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
