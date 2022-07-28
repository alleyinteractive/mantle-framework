<?php
/**
 * Autoloaded File to support Testing
 *
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use function Mantle\Support\Helpers\tap;

/**
 * Retrieve an instance of the Installation Manager
 *
 * The manager can install the Mantle Testing Framework but will not by default.
 * Call {@see Installation_Manager::install()} to install or use the
 * {@see install()} helper.
 *
 * @return Installation_Manager
 */
function manager(): Installation_Manager {
	require_once __DIR__ . '/preload.php';

	return Installation_Manager::instance();
}

/**
 * Install the Mantle Testing Framework
 *
 * @param callable $callback_after_preload Callback to invoke before WordPress is loaded.
 * @return Installation_Manager
 */
function install( callable $callback_after_preload = null ): Installation_Manager {
	return tap(
		manager(),
		fn ( Installation_Manager $manager ) => $manager->after( $callback_after_preload ),
	)->install();
}
