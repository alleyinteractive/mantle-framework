<?php
/**
 * View_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Http\View\Factory;
use Mantle\Framework\Http\View\View_Loader;
use Mantle\Framework\Service_Provider;

use function Mantle\Framework\Helpers\tap;

/**
 * View Service Provider
 */
class View_Service_Provider extends Service_Provider {

	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_loader();
		$this->register_factory();
	}

	/**
	 * Register the view loader.
	 */
	protected function register_loader() {
		$this->app->singleton(
			'view.loader',
			function ( $app ) {
				return tap(
					new View_Loader( $app->get_base_path() ),
					function ( View_Loader $loader ) {
						// Register the base view folder for the project.
						$loader->add_path( $this->app->get_base_path( 'views/' ) );
					}
				);
			}
		);
	}

	/**
	 * Register the view factory.
	 */
	protected function register_factory() {
		$this->app->singleton(
			'view',
			function( $app ) {
				$factory = new Factory( $app );
				$factory->share( 'app', $app );
				return $factory;
			}
		);
	}
}
