<?php
/**
 * Has_Factory trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use Mantle\Database\Factory\Factory;

/**
 * Model Database Factory
 */
trait Has_Factory {
	/**
	 * Create a builder for the model.
	 *
	 * @param array|callable $state Default state array or callable that will be invoked to set state.
	 * @return \Mantle\Database\Factory\Factory<static>
	 */
	public static function factory( array|callable|null $state = null ) {
		$factory = static::new_factory() ?: Factory::factory_for_model( static::class );

		return $factory
			->as_models()
			->with_model( static::class )
			->when( is_array( $state ) || is_callable( $state ), fn ( Factory $factory ) => $factory->state( $state ) );
	}

	/**
	 * Create a new factory instance for the model.
	 *
	 * Optional: allows for the model factory to be overridden by application code.
	 *
	 * @return \Mantle\Database\Factory\Factory<static>|null
	 */
	protected static function new_factory(): ?Factory {
		return null;
	}
}
