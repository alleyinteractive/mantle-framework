<?php
/**
 * Autoloaded File to support Testing
 *
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing;

/**
 * Retrieve the Mantle Framework Directory
 *
 * @return string
 */
function get_framework_directory(): string {
	$preload_path = '/src/mantle/framework/testing/preload.php';

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


	$dir = preg_replace( '#/mantle-framework/.*$#', '/mantle-framework', __DIR__ );
	if ( is_dir( $dir ) ) {
		return $dir;
	}

	return dirname( __DIR__, 4 );
}

/**
 * Install the Mantle Tests Framework
 *
 * @param callable $callback_after_preload Callback to invoke before WordPress is loaded.
 * @return void
 */
function install( callable $callback_after_preload = null ): void {
	$dir = get_framework_directory();

	if ( ! file_exists( $dir . '/src/mantle/framework/testing/preload.php' ) ) {
		echo "ERROR: Failed to locate valid mantle-framework location. \n";
		echo "Location: {$dir} \n";
		exit( 1 );
	}

	require_once $dir . '/src/mantle/framework/testing/preload.php';

	if ( $callback_after_preload ) {
		$callback_after_preload();
	}

	try {
		require_once $dir . '/src/mantle/framework/testing/wordpress-bootstrap.php';
	} catch ( \Throwable $throwable ) {
		echo "ERROR: Failed to load WordPress!\n";
		echo "{$throwable}\n";
		exit( 1 );
	}
}
