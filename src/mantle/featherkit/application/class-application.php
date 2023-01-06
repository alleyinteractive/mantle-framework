<?php
/**
 * Application class file
 *
 * @package Mantle
 */

namespace Mantle\Featherkit\Application;

use Mantle\Application\Application as Base_Application;

/**
 * Featherkit Application
 *
 * Most of the built-in caching and functionality that comes with
 * `alleyinteractive/mantle` is excluded here.
 */
class Application extends Base_Application {
	/**
	 * Determine if the application is cached.
	 *
	 * @return bool
	 */
	public function is_configuration_cached(): bool {
		return false;
	}

	/**
	 * Determine if events are cached.
	 *
	 * @return bool
	 */
	public function is_events_cached(): bool {
		return false;
	}

	/**
	 * Check if the application is running in the console.
	 *
	 * @return bool
	 */
	public function is_running_in_console(): bool {
		return false;
	}

	/**
	 * Check if the application is running in console isolation mode.
	 *
	 * @return bool
	 */
	public function is_running_in_console_isolation(): bool {
		return false;
	}
}
