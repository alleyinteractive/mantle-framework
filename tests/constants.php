<?php
/**
 * Constants for PHPStan
 */

global $wpdb;

$wpdb = new wpdb( '', '', '', '' );

defined( 'WP_TESTS_DOMAIN' ) || define( 'WP_TESTS_DOMAIN', 'example.org' );
defined( 'WP_TESTS_EMAIL' ) || define( 'WP_TESTS_EMAIL', 'admin@example.org' );
defined( 'WP_TESTS_TITLE' ) || define( 'WP_TESTS_TITLE', 'Test Site' );

defined( 'WP_PHP_BINARY' ) || define( 'WP_PHP_BINARY', 'php' );

defined( 'WPLANG' ) || define( 'WPLANG', '' );
