<?php
/**
 * Register_Aliases class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Framework\Alias_Loader;
use Mantle\Application\Application;
use Mantle\Facade\Facade;
use Mantle\Framework\Manifest\Package_Manifest;

/**
 * Register the Aliases for the application
 */
class Register_Aliases {
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		Alias_Loader::get_instance(
			array_merge(
				(array) $app->make( 'config' )->get( 'app.aliases' ),
				(array) $app->make( Package_Manifest::class )->aliases()
			)
		)->register();
	}
}
