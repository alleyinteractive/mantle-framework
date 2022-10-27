<?php
/**
 * Register_Cli_Commands class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Framework\Application;
use Mantle\Contracts\Console\Kernel as Console_Contract;
use Mantle\Contracts\Kernel as Kernel_Contract;

/**
 * Register CLI Commands from Service Providers
 */
class Register_Cli_Commands {
	/**
	 * Register any CLI Commands from the Service Providers
	 *
	 * @param Application     $app    Application instance.
	 * @param Kernel_Contract $kernel Kernel instance.
	 */
	public function bootstrap( Application $app, Kernel_Contract $kernel ) {
		$providers = $app->get_providers();

		foreach ( $providers as $provider ) {
			// $provider->register_commands();
		}

		// Register the commands from the Console Application Kernel.
		if ( $kernel instanceof Console_Contract ) {
			$kernel->register_commands();
		}
	}
}
