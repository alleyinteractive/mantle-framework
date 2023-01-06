<?php
/**
 * Model_Register class file.
 *
 * @package Mantle
 */

namespace Mantle\Database;

use Mantle\Database\Model\Model;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Framework\Manifest\Model_Manifest;
use Mantle\Support\Service_Provider;

/**
 * Model Service Provider
 */
class Model_Service_Provider extends Service_Provider {
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
		Model::set_event_dispatcher( $this->app['events'] );

		// Allow the configuration to disable discovery.
		if ( $this->app['config']->get( 'models.disable_discovery', false ) ) {
			return;
		}

		$configuration = (array) $this->app['config']->get( 'models.register', [] );

		// Allows models to always be booted on each request to register whatever side-effects they desire.
		$this->set_models_to_register(
			array_merge(
				$configuration,
				$this->app[ Model_Manifest::class ]->models()
			)
		);
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @throws Provider_Exception Thrown on invalid model.
	 */
	public function boot() {
		parent::boot();

		if ( empty( $this->models ) ) {
			return;
		}

		foreach ( $this->models as $model ) {
			if ( class_exists( $model ) ) {
				$model::boot_if_not_booted();
			}
		}
	}

	/**
	 * Set the models to register.
	 *
	 * @param string[] $models Models to register.
	 */
	public function set_models_to_register( array $models ) {
		$this->models = array_unique( $models );
	}

	/**
	 * Use the 'init' hook with a priority of 99.
	 */
	public function on_init() {
		static::register_internal_taxonomy();
	}

	/**
	 * Register the internal taxonomy for post <--> post relationships.
	 */
	public static function register_internal_taxonomy() {
		register_taxonomy(
			Relation::RELATION_TAXONOMY,
			array_keys( get_post_types() ),
			[
				'public'       => false,
				'rewrite'      => false,
				'show_in_rest' => false,
			]
		);
	}
}
