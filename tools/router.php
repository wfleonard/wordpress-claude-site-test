<?php
/**
 * Router for PHP's built in server so WordPress pretty permalinks work locally.
 * Usage (from the WordPress root): php -S localhost:8080 path/to/tools/router.php
 *
 * @author William Leonard, CTI Global <bill.leonard@cticorp.com>
 */

$saxon_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$saxon_file = $_SERVER['DOCUMENT_ROOT'] . $saxon_path;

if ( '/' !== $saxon_path && file_exists( $saxon_file ) && ! is_dir( $saxon_file ) ) {
	return false;
}
if ( is_dir( $saxon_file ) && file_exists( rtrim( $saxon_file, '/' ) . '/index.php' ) ) {
	require rtrim( $saxon_file, '/' ) . '/index.php';
	return;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
