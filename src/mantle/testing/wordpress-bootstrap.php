<?php // phpcs:disable
/**
 * This file installs and loads WordPress for running tests
 *
 * @package Mantle
 */

use Mantle\Testing\Doubles\MockPHPMailer;
use Mantle\Testing\Utils;
use Mantle\Testing\WP_Die;

use function Mantle\Testing\get_framework_directory;
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
       $table_prefix,
       $wp_theme_directories,
       $PHP_SELF;

// Load the configuration.
if ( defined( 'WP_TESTS_CONFIG_FILE_PATH' ) && ! empty( WP_TESTS_CONFIG_FILE_PATH ) && is_readable( WP_TESTS_CONFIG_FILE_PATH ) ) {
	$config_file_path = WP_TESTS_CONFIG_FILE_PATH;
} elseif ( false === strpos( __DIR__, '/wp-content/' ) ) {
	/**
	 * Install WordPress automatically in the /tmp/wordpress folder.
	 *
	 * Retrieves the latest installation command from Mantle's GitHub and runs it
	 * to install WordPress to a temporary folder (/tmp/wordpress by default).
	 * This unlocks the ability to run `composer run phpunit` both locally and in
	 * CI tests.
	 */

	defined( 'WP_TESTS_INSTALL_PATH' ) || define( 'WP_TESTS_INSTALL_PATH', '/tmp/wordpress' );

	$config_file_path = WP_TESTS_INSTALL_PATH . '/wp-tests-config.php';

	// Install WordPress if we're not in the sub-process that installs WordPress.
	if ( ! defined( 'WP_INSTALLING' ) || ! WP_INSTALLING ) {
		echo 'WordPress installation not found, installing in temporary directory: ' . WP_TESTS_INSTALL_PATH . PHP_EOL;

		$dir = get_framework_directory();

		// Download the latest installation command from GitHub and install WordPress.
		$cmd = sprintf(
			'WP_CORE_DIR=%s curl -s %s | bash -s %s %s %s %s %s true',
			WP_TESTS_INSTALL_PATH,
			// 'https://raw.githubusercontent.com/alleyinteractive/mantle-framework/main/bin/install-wp-tests.sh',
			'https://raw.githubusercontent.com/alleyinteractive/mantle-framework/remove-bin-install/bin/install-wp-tests.sh',
			defined( 'DB_NAME' ) ? DB_NAME : 'wordpress_unit_tests',
			defined( 'DB_USER' ) ? DB_USER : 'root',
			defined( 'DB_PASSWORD' ) ? DB_PASSWORD : 'root',
			defined( 'DB_HOST' ) ? DB_HOST : 'localhost',
			'latest',
		);

		$resp = system( $cmd, $retval );

		if ( 0 !== $retval ) {
			echo PHP_EOL . 'Error installing WordPress!';
			exit( 1 );
		}
	}
} else {
	// The project is being loaded from inside a WordPress installation.
	$config_file_path = preg_replace( '#/wp-content/.*$#', '/wp-tests-config.php', __DIR__ );
}

if ( is_readable( $config_file_path ) ) {
	echo "Using configuration file: [{$config_file_path}]\n";
	require_once $config_file_path;
} elseif ( ! defined( 'WP_INSTALLING' ) || ! WP_INSTALLING ) {
	echo "No wp-tests-config.php file found, using default configuration.\n";
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
	echo 'Running as single site... To run multisite, pass WP_TESTS_MULTISITE=1' . PHP_EOL;
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
