<?php
/**
 * Register_Facades class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Framework\Alias_Loader;
use Mantle\Framework\Application;
use Mantle\Framework\Facade\Facade;

/**
 * Register the Facades for the Application
 */
class Register_Facades {
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		Facade::clear_resolved_instances();
		Facade::set_facade_application( $app );

		// Load the Facades from the config.
		$aliases = $app->config->get( 'app.aliases' );
		Alias_Loader::get_instance( (array) $aliases )->register();
	}
}
