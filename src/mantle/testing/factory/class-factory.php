<?php
/**
 * Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Support\Pipeline;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\tap;

/**
 * Base Factory
 *
 * @template TObject
 */
abstract class Factory {
	/**
	 * Flag to return the factory as a model.
	 *
	 * @var bool
	 */
	protected bool $as_models = false;

	/**
	 * Array of pipes (middleware) to run through.
	 *
	 * @var array<callable>
	 */
	public array $middleware = [];

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	abstract public function create( array $args = [] );

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return mixed
	 */
	abstract public function get_object_by_id( int $object_id );

	/**
	 * Generate models from the factory.
	 *
	 * @return static
	 */
	public function as_models() {
		return tap(
			clone $this,
			fn ( $factory ) => $factory->as_models = true,
		);
	}

	/**
	 * Generate core WordPress objects from the factory.
	 *
	 * @return static
	 */
	public function as_objects() {
		return tap(
			clone $this,
			fn ( $factory ) => $factory->as_models = false,
		);
	}

	/**
	 * Create a new factory instance with middleware.
	 *
	 * @param array $middleware Middleware to run the factory through.
	 * @return static
	 */
	public function with_middleware( $middleware ) {
		return tap(
			clone $this,
			fn ( $factory ) => $factory->middleware = collect( $this->middleware )
				->merge( $middleware )
				->all(),
		);
	}

	/**
	 * Creates multiple objects.
	 *
	 * @param int   $count Amount of objects to create.
	 * @param array $args  Optional. The arguments for the object to create. Default is empty array.
	 *
	 * @return array<int, int>
	 */
	public function create_many( int $count, array $args = [] ) {
		return collect()
			->pad( $count, null )
			->map(
				fn() => $this->create( $args ),
			)
			->to_array();
	}

	/**
	 * Creates an object and returns its object.
	 *
	 * @param array $args Optional. The arguments for the object to create. Default is empty array.
	 * @return TObject|Core_Object The created object.
	 */
	public function create_and_get( $args = [] ) {
		$object_id = $this->create( $args );

		if ( is_wp_error( $object_id ) ) {
			return $object_id;
		}

		return $this->get_object_by_id( $object_id );
	}

	/**
	 * Pass arguments through the middleware and return a core object.
	 *
	 * @param array  $args  Arguments to pass through the middleware.
	 * @param string $class Model class name.
	 * @return TObject|Core_Object
	 */
	protected function make( array $args, string $class ) {
		return Pipeline::make()
			->send( $args )
			->through( $this->middleware )
			->then(
				fn ( array $args ) => $class::create( $args )
			);
	}
}
