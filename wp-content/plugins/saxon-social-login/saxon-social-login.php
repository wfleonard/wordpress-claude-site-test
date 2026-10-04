<?php
/**
 * Plugin Name:       Saxon Social Login
 * Description:       Adds "Sign in with Google" and "Sign in with Microsoft" to the WordPress login screen alongside the normal username and password. Only existing users can sign in; nobody is registered automatically. Configure with constants in wp-config.php.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            William Leonard, Saxon Enterprises, Inc.
 * License:           GPL-2.0-or-later
 * Text Domain:       saxon-social-login
 *
 * @package SaxonSocialLogin
 * @author  William Leonard, Saxon Enterprises, Inc.
 */

defined( 'ABSPATH' ) || exit;

define( 'SAXON_SSO_VERSION', '1.0.0' );
define( 'SAXON_SSO_FILE', __FILE__ );
define( 'SAXON_SSO_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAXON_SSO_URL', plugin_dir_url( __FILE__ ) );

require SAXON_SSO_DIR . 'includes/providers.php';
require SAXON_SSO_DIR . 'includes/flow.php';
require SAXON_SSO_DIR . 'includes/login-screen.php';
require SAXON_SSO_DIR . 'includes/profile.php';
