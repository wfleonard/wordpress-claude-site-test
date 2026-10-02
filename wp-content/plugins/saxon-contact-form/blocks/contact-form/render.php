<?php
/**
 * Server render for the saxon/contact-form block.
 *
 * @package SaxonContactForm
 */

defined( 'ABSPATH' ) || exit;

echo saxon_cf_render_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template.
