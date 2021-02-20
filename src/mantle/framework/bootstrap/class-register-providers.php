<?php
/**
 * Register_Providers class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Framework\Application;
use Mantle\Contracts\Bootstrapable as Bootstrapable_Contract;

/**
 * Register the Service Providers with the Application from the config.
 */
class Register_Providers implements Bootstrapable_Contract {
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		$app->register_configured_providers();
	}
}
