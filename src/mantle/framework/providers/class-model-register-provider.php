<?php
/**
 * Model_Register class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Service_Provider;

/**
 * Model Register Service Provider
 *
 * Allows models to always be booted on each request to register whatever side-effects they desire.
 */
class Model_Register_Provider extends Service_Provider {
	/**
	 * Models to register for the application.
	 *
	 * @var string[]
	 */
	protected $models = [];

	/**
	 * Register the service provider.
	 */
	public function register() {
		$this->set_models_to_register( $this->app['config']->get( 'models.register' ) );
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @throws Provider_Exception Thrown on invalid model.
	 */
	public function boot() {
		if ( empty( $this->models ) ) {
			return;
		}

		foreach ( $this->models as $model ) {
			$model::boot_if_not_booted();
		}
	}

	/**
	 * Set the models to register.
	 *
	 * @param string[] $models Models to register.
	 */
	public function set_models_to_register( array $models ) {
		$this->models = $models;
	}
}
