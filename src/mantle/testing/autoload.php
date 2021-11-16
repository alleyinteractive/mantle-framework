<?php
/**
 * Autoloaded File to support Testing
 *
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 *
 * @package Mantle
 */

namespace Mantle\Testing;

/**
 * Retrieve the Mantle Framework Directory
 *
 * @return string
 */
function get_framework_directory(): string {
	$preload_path = '/src/mantle/testing/preload.php';

	$mantle_dir = getenv( 'MANTLE_FRAMEWORK_DIR' );

	if ( getenv( 'MANTLE_FRAMEWORK_DIR' ) ) {
		return (string) getenv( 'MANTLE_FRAMEWORK_DIR' );
	}

	if ( defined( 'MANTLE_FRAMEWORK_DIR' ) ) {
		return (string) MANTLE_FRAMEWORK_DIR;
	}

	$mantle_dir = dirname( __DIR__ ) . '/vendor/alleyinteractive/mantle-framework';
	if ( file_exists( $mantle_dir . $preload_path ) ) {
		return $mantle_dir;
	}

	$dir = preg_replace( '#/mantle-framework/src/.*$#', '/mantle-framework/src/', __DIR__ );
	if ( is_dir( $dir ) ) {
		return dirname( $dir );
	}

	return dirname( __DIR__, 3 );
}

/**
 * Install the Mantle Tests Framework
 *
 * @param callable $callback_after_preload Callback to invoke before WordPress is loaded.
 * @return void
 */
function install( callable $callback_after_preload = null ): void {
	$dir = get_framework_directory();

	if ( ! file_exists( $dir . '/src/mantle/testing/preload.php' ) ) {
		echo "ERROR: Failed to locate valid mantle-framework location. \n";
		echo "Location: {$dir} \n";
		echo 'Current file: ' . __FILE__ . PHP_EOL;
		exit( 1 );
	}

	require_once $dir . '/src/mantle/testing/preload.php';

	if ( $callback_after_preload ) {
		$callback_after_preload();
	}

	try {
		require_once $dir . '/src/mantle/testing/wordpress-bootstrap.php';
	} catch ( \Throwable $throwable ) {
		echo "ERROR: Failed to load WordPress!\n";
		echo "{$throwable}\n";
		exit( 1 );
	}
}
