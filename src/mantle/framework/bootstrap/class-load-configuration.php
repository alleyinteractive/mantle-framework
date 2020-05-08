<?php
/**
 * Load_Configuration class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Framework\Application;

/**
 * Boot the Application
 */
class Load_Configuration {
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		$app->boot();
	}
}
