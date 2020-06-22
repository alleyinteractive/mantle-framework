<?php
/**
 * View_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Http\View\Factory;
use Mantle\Framework\Service_Provider;

/**
 * View Service Provider
 */
class View_Service_Provider extends Service_Provider {

	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->register_factory();
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
