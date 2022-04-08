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
 * Install the Mantle Tests Framework
 *
 * @param callable $callback_after_preload Callback to invoke before WordPress is loaded.
 * @return void
 */
function install( callable $callback_after_preload = null ): void {
	if ( ! file_exists( __DIR__ . '/preload.php' ) ) {
		echo "ERROR: Failed to locate valid Mantle testing preload file. \n";
		echo 'Current file: ' . __FILE__ . PHP_EOL;
		exit( 1 );
	}

	require_once __DIR__ . '/preload.php';

	if ( $callback_after_preload ) {
		$callback_after_preload();
	}

	try {
		require_once __DIR__ . '/wordpress-bootstrap.php';
	} catch ( \Throwable $throwable ) {
		echo "ERROR: Failed to load WordPress!\n";
		echo "{$throwable}\n";
		exit( 1 );
	}
}
