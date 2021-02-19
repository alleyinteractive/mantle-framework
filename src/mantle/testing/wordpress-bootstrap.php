<?php // phpcs:disable
/**
 * This file installs and loads WordPress for running tests
 *
 * @package Mantle
 */

use Mantle\Testing\Doubles\MockPHPMailer;
use Mantle\Testing\Utils;
use Mantle\Testing\WP_Die;
use function Mantle\Testing\tests_add_filter;

require_once __DIR__ . '/class-utils.php';
require_once __DIR__ . '/class-wp-die.php';

/*
 * Globalize some WordPress variables, because PHPUnit loads this file inside a function.
 * See: https://github.com/sebastianbergmann/phpunit/issues/325
 */
global $wpdb,
       $current_site,
       $current_blog,
       $wp_rewrite,
       $shortcode_tags,
       $wp,
       $phpmailer,
       $table_prefix,
       $wp_theme_directories,
       $PHP_SELF;

// Load the configuration.
if ( defined( 'WP_TESTS_CONFIG_FILE_PATH' ) && ! empty( WP_TESTS_CONFIG_FILE_PATH ) && is_readable( WP_TESTS_CONFIG_FILE_PATH ) ) {
	$config_file_path = WP_TESTS_CONFIG_FILE_PATH;
} else {
	$config_file_path = preg_replace( '#/wp-content/.*$#', '/wp-tests-config.php', __DIR__ );
}

if ( is_readable( $config_file_path ) ) {
	echo "Using configuration file: [{$config_file_path}]\n";
	require_once $config_file_path;
}

Utils::setup_configuration();
Utils::reset_server();

define( 'WP_TESTS_TABLE_PREFIX', $table_prefix );
define( 'DIR_TESTDATA', __DIR__ . '/data' );

/*
 * Cron tries to make an HTTP request to the site, which always fails,
 * because tests are run in CLI mode only.
 */
define( 'DISABLE_WP_CRON', true );

define( 'WP_MEMORY_LIMIT', -1 );
define( 'WP_MAX_MEMORY_LIMIT', -1 );

$PHP_SELF            = '/index.php';
$GLOBALS['PHP_SELF'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Should we run in multisite mode?
$multisite = ( '1' === getenv( 'WP_MULTISITE' ) );
$multisite = $multisite || ( defined( 'WP_TESTS_MULTISITE' ) && WP_TESTS_MULTISITE );
$multisite = $multisite || ( defined( 'MULTISITE' ) && MULTISITE );

// Override the PHPMailer.
require_once __DIR__ . '/doubles/class-mockphpmailer.php';
$phpmailer = new MockPHPMailer( true );

if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	define( 'WP_DEFAULT_THEME', 'default' );
}
$wp_theme_directories = [];
$installing_wp        = defined( 'WP_INSTALLING' ) && WP_INSTALLING;

if ( ! $installing_wp && '1' !== getenv( 'WP_TESTS_SKIP_INSTALL' ) ) {
	system( WP_PHP_BINARY . ' ' . escapeshellarg( __DIR__ . '/install-wordpress.php' ) . ' ' . $multisite, $retval );
	if ( 0 !== $retval ) {
		exit( $retval );
	}
}

if ( $multisite ) {
	if ( ! $installing_wp ) {
		echo 'Running as multisite...' . PHP_EOL;
	}
	defined( 'MULTISITE' ) or define( 'MULTISITE', true );
	defined( 'SUBDOMAIN_INSTALL' ) or define( 'SUBDOMAIN_INSTALL', false );
	$GLOBALS['base'] = '/';
} elseif ( ! $installing_wp ) {
	echo 'Running as single site... To run multisite, use -c tests/phpunit/multisite.xml' . PHP_EOL;
}
unset( $multisite );

$GLOBALS['_wp_die_disabled'] = false;

// Allow tests to override wp_die().
tests_add_filter( 'wp_die_handler', [ WP_Die::class, 'get_toggled_handler' ] );

// Use the Spy REST Server instead of default.
tests_add_filter( 'wp_rest_server_class', [ Utils::class, 'wp_rest_server_class_filter' ], PHP_INT_MAX );

// Load WordPress.
require_once ABSPATH . '/wp-settings.php';

/*
 * See https://core.trac.wordpress.org/ticket/48605.
 */
if ( isset( $_SERVER['REQUEST_TIME'] ) ) {
	$_SERVER['REQUEST_TIME'] = (int) $_SERVER['REQUEST_TIME'];
}
if ( isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
	$_SERVER['REQUEST_TIME_FLOAT'] = (float) $_SERVER['REQUEST_TIME_FLOAT'];
}

// Delete any default posts & related data.
if ( is_blog_installed() ) {
	Utils::delete_all_posts();
}
