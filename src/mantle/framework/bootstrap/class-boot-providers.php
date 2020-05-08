<?php
/**
 * Boot_Providers class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Framework\Application;

/**
 * Boot the Application
 */
class Boot_Providers {
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		$app->boot();
	}
}
