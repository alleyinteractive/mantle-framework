<?php
/**
 * Register_Cli_Commands class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Framework\Application;

/**
 * Register CLI Commands from Service Providers
 */
class Register_Cli_Commands {
	/**
	 * Register any CLI Commands from the Service Providers
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		$providers = $app->get_providers();

		foreach ( $providers as $provider ) {
			$provider->register_commands();
		}
	}
}
