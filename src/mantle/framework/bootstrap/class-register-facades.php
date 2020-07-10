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
use Mantle\Framework\Package_Manifest;

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
		Alias_Loader::get_instance(
			array_merge(
				(array) $app->config->get( 'app.aliases' ),
				(array) $app->make( Package_Manifest::class )->aliases()
			)
		)->register();
	}
}
