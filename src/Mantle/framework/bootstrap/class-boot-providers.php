<?php
/**
 * Boot_Providers class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Application\Application;

/**
 * Boot the Application
 */
class Boot_Providers {
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ): void {
		$app->boot();
	}
}
