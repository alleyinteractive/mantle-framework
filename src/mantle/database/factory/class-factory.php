<?php
/**
 * Factory class file.
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag, Squiz.Commenting.FunctionComment.ParamNameNoMatch
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Closure;
use Faker\Generator;
use Mantle\Contracts\Database\Core_Object;
use Mantle\Database\Model\Model;
use Mantle\Support\Collection;
use Mantle\Support\Pipeline;
use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Macroable;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\tap;

/**
 * Base Factory
 *
 * @template TModel of \Mantle\Database\Model\Model = \Mantle\Database\Model\Model
 * @template TObject = object
 * @template TReturnValue = mixed
 *
 * @method \Mantle\Database\Factory\Fluent_Factory<TModel, TObject, TReturnValue> count(int $count)
 */
abstract class Factory {
	use Concerns\Resolves_Factories;
	use Conditionable;
	use Macroable {
		__call as macro_call;
	}

	/**
	 * Flag to return the factory as a model.
	 */
	protected bool $as_models = false;

	/**
	 * Array of pipes (middleware) to run through.
	 *
	 * @var \Mantle\Support\Collection<int, callable(array $args, Closure $next): mixed>
	 */
	public Collection $middleware;

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string
	 */
	protected string $model;

	/**
	 * Flag to use slashes on the arguments.
	 */
	public bool $slash = false;

	/**
	 * Constructor.
	 *
	 * @param Generator $faker The Faker instance.
	 * @phpstan-param Generator&\Mantle\Faker\Faker_Provider $faker
	 */
	public function __construct( protected Generator $faker ) {
		$this->middleware = new Collection();
	}

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	abstract public function definition(): array;

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return TModel|TObject|null
	 */
	abstract public function get_object_by_id( int $object_id );

	/**
	 * Retrieves an object by query.
	 *
	 * @param array<string, mixed> $query The query to use to retrieve the object.
	 *                                    Passed to the underlying model's query builder.
	 * @return TModel|null
	 */
	public function get_object_by_query( array $query ): ?Model {
		$model = $this->get_model();

		return $model::query()->where( $query )->first();
	}

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return int|null
	 */
	public function create( array $args = [] ): mixed {
		return $this->make( $args )?->id();
	}

	/**
	 * Creates an object and returns its ID.
	 *
	 * @deprecated Use create() or create_and_get() instead.
	 *
	 * @param array $args The arguments.
	 */
	public function create_object( array $args ): int|null {
		return $this->create( $args );
	}

	/**
	 * Creates an object and returns its object.
	 *
	 * @param array $attributes The attributes to use when matching and creating the object.
	 * @param array $values The values to use when creating the object.
	 * @return TReturnValue The created object.
	 */
	public function first_or_create( array $attributes, array $values = [] ) {
		$object = $this->get_object_by_query( $attributes );

		if ( ! $object instanceof Model ) {
			return $this->create_and_get( array_merge( $attributes, $values ) );
		}

		return $object;
	}

	/**
	 * Generate models from the factory.
	 *
	 * @return static<TModel, TObject, TModel>
	 */
	public function as_models() {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->as_models = true,
		);
	}

	/**
	 * Generate core WordPress objects from the factory.
	 *
	 * @return static<TModel, TObject, TObject>
	 */
	public function as_objects() {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->as_models = false,
		);
	}

	/**
	 * Add a global middleware to the factory.
	 *
	 * This middleware will be applied to all factory calls. Generally you will
	 * not need to use this unless you plan to modify all calls of a factory in a
	 * testing suite.
	 *
	 * @param callable $middleware Middleware to run the factory through.
	 * @phpstan-param (callable(array $args, \Closure $next): TModel) $middleware
	 */
	public function with_global_middleware( callable $middleware ): static {
		$this->middleware->push( $middleware );

		return $this;
	}

	/**
	 * Create a new factory instance with middleware.
	 *
	 * @param callable $middleware Middleware to run the factory through.
	 * @phpstan-param (callable(array $args, \Closure $next): TModel) $middleware
	 */
	public function with_middleware( callable $middleware ): static {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->middleware = $this->middleware->merge( $middleware ),
		);
	}

	/**
	 * Create a new factory instance without any middleware.
	 *
	 * This will return the factory to its original state with only the factory
	 * definition applied.
	 *
	 * @return static
	 */
	public function without_middleware() {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->middleware = new Collection(),
		);
	}

	/**
	 * Specify the model to use when creating objects.
	 *
	 * @throws \InvalidArgumentException If the model does not extend from the base model class.
	 *
	 * @template TNewModel of \Mantle\Database\Model\Model
	 *
	 * @param class-string<TNewModel> $model The model to use.
	 * @return static<TNewModel, TObject, TReturnValue>
	 */
	public function with_model( string $model ) {
		// Validate that model extends from the base model class.
		if ( ! is_subclass_of( $model, Model::class ) ) {
			throw new \InvalidArgumentException( 'Model must extend from the base model class.' );
		}

		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->model = $model,
		);
	}

	/**
	 * Retrieve the model to use when creating objects.
	 *
	 * @return class-string<TObject>
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Create a new factory instance with arguments passed to `wp_slash()` before creating.
	 *
	 * @param bool $value Whether to use slashes or not.
	 */
	public function slash( bool $value = true ): static {
		return tap(
			clone $this,
			fn ( Factory $factory ) => $factory->slash = $value,
		);
	}

	/**
	 * Add a new state transformation to the factory. Functions the same as
	 * middleware but supports returning an array of attributes vs a closure.
	 *
	 * @param (callable(array<string, mixed>): array<string, mixed>|array<string, mixed>) $state The state transformation.
	 */
	public function state( array|callable $state ): static {
		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $state ) {
				$args = array_merge(
					$args,
					is_callable( $state ) ? $state( $args ) : $state,
				);

				return $next( $args );
			},
		);
	}

	/**
	 * Alias for `state()`.
	 *
	 * @param (callable(array<string, mixed>): array<string, mixed>|array<string, mixed>) $state The state transformation.
	 */
	public function with( array|callable $state ): static {
		return $this->state( $state );
	}

	/**
	 * Creates multiple objects.
	 *
	 * @param int   $count Amount of objects to create.
	 * @param array $args  Optional. The arguments for the object to create. Default is empty array.
	 *
	 * @return array<int, int>
	 */
	public function create_many( int $count, array $args = [] ): array {
		return collect()
			->pad( $count, null )
			->map( fn () => $this->create( $args ) )
			->to_array();
	}


	/**
	 * Creates multiple objects and returns their objects.
	 *
	 * @param int   $count Amount of objects to create.
	 * @param array $args  Optional. The arguments for the object to create. Default is empty array.
	 *
	 * @return array<int, TReturnValue>
	 */
	public function create_many_and_get( int $count, array $args = [] ): array {
		return collect()
			->pad( $count, null )
			->map( fn () => $this->create_and_get( $args ) )
			->all();
	}

	/**
	 * Creates an object and returns its object.
	 *
	 * @param array $args Optional. The arguments for the object to create. Default is empty array.
	 * @return TReturnValue The created object.
	 */
	public function create_and_get( array $args = [] ) {
		return $this->get_object_by_id( $this->create( $args ) );
	}

	/**
	 * Pass arguments through the middleware and return a core object.
	 *
	 * @param array $args  Arguments to pass through the middleware.
	 * @return TObject|Core_Object|null
	 */
	protected function make( array $args ) {
		// Apply the factory definition to top of the middleware stack.
		$this->middleware->prepend( $this->apply_definition() );

		// Append the arguments passed to make() as the last state values to apply.
		$factory = $this->state( $args );

		return Pipeline::make()
			->send( [] )
			->through( $factory->middleware->all() )
			->then(
				function ( array $args ) use ( $factory ): Model {
					if ( $factory->slash ) {
						$args = wp_slash( $args );
					}

					return $this->get_model()::create( $args );
				},
			);
	}

	/**
	 * Load the factory's definition and make a new instance of the factory.
	 */
	public function apply_definition(): Closure {
		return fn ( array $args, Closure $next ) => $next(
			array_merge( $args, $this->definition() ),
		);
	}

	/**
	 * Create a new fluent factory instance.
	 */
	protected function create_fluent_factory(): Fluent_Factory {
		return new Fluent_Factory(
			clone $this,
			$this->faker,
		);
	}

	/**
	 * Magic method to proxy calls to the fluent factory.
	 *
	 * @param string       $method The method name.
	 * @param array<mixed> $args   The arguments.
	 */
	public function __call( string $method, array $args ): mixed {
		if ( static::has_macro( $method ) ) {
			return $this->macro_call( $method, $args );
		}

		return $this->create_fluent_factory()->$method( ...$args );
	}
}
