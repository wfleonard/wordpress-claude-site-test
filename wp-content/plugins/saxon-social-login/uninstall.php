<?php
/**
 * Remove connected account links when the plugin is deleted from the Plugins screen.
 * Deactivating the plugin keeps them.
 *
 * @package SaxonSocialLogin
 * @author  William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_metadata( 'user', 0, '_saxon_sso_google', '', true );
delete_metadata( 'user', 0, '_saxon_sso_microsoft', '', true );
