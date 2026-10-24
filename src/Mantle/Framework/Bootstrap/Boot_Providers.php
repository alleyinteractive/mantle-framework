<?php
/**
 * Boot_Providers class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Contracts\Application;
use Mantle\Contracts\Bootstrapable;
use Mantle\Contracts\Kernel;

/**
 * Boot the Application
 */
class Boot_Providers implements Bootstrapable {
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 * @param Kernel|null $kernel Kernel instance.
	 */
	public function bootstrap( Application $app, ?Kernel $kernel ): void {
		$app->boot();
	}
}
