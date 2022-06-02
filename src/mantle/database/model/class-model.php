<?php
/**
 * Model class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use ArrayAccess;
use JsonSerializable;
use Mantle\Contracts\Http\Routing\Url_Routable;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Contracts\Support\Jsonable;
use Mantle\Database\Query\Builder;
use Mantle\Support\Collection;
use Mantle\Support\Forward_Calls;
use Mantle\Support\Str;

use function Mantle\Support\Helpers\class_basename;
use function Mantle\Support\Helpers\class_uses_recursive;
use function Mantle\Support\Helpers\tap;

/**
 * Database Model
 *
 * @mixin \Mantle\Database\Query\Builder
 */
abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, Url_Routable {
	use Forward_Calls,
		Concerns\Has_Aliases,
		Concerns\Has_Attributes,
		Concerns\Has_Events,
		Concerns\Has_Global_Scopes,
		Concerns\Has_Relationships;

	/**
	 * The array of booted models.
	 *
	 * @var array
	 */
	protected static $booted = [];

	/**
	 * The array of trait initializers that will be called on each new instance.
	 *
	 * @var array
	 */
	protected static $trait_initializers = [];

	/**
	 * The array of global scopes on the model.
	 *
	 * @var array
	 */
	protected static $global_scopes = [];

	/**
	 * An object's registerable name (post type, taxonomy, etc.).
	 *
	 * @var string
	 */
	public static $object_name;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * Indicates if the model exists.
	 *
	 * @var bool
	 */
	public $exists = false;

	/**
	 * The relations to eager load on every query.
	 *
	 * @var string[]
	 */
	protected $with = [];

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		$this->boot_if_not_booted();
		$this->initialize_traits();

		$this->set_attributes( (array) $object );
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param object|string|int $object Object to retrieve.
	 * @return static|null
	 */
	abstract public static function find( $object );

	/**
	 * Find a model or throw an exception.
	 *
	 * @param object|string|int $object Object to retrieve.
	 * @return static
	 *
	 * @throws Model_Not_Found_Exception Thrown on missing resource.
	 */
	public static function find_or_fail( $object ) {
		$find = static::find( $object );
		if ( $find ) {
			return $find;
		}

		throw ( new Model_Not_Found_Exception() )->set_model( __CLASS__, $object );
	}

	/**
	 * Query builder class to use.
	 *
	 * @return string|null
	 */
	public static function get_query_builder_class(): ?string {
		return null;
	}

	/**
	 * Determine if the model has a given scope.
	 *
	 * @param string $scope Scope name.
	 * @return bool
	 */
	public function has_named_scope( string $scope ): bool {
		return method_exists( $this, 'scope' . ucfirst( $scope ) );
	}

	/**
	 * Apply the given named scope if possible.
	 *
	 * @param string $scope Scope name.
	 * @param array  $parameters Scope parameters.
	 * @return mixed
	 */
	public function call_named_scope( string $scope, array $parameters = [] ) {
		return $this->{ 'scope' . ucfirst( $scope ) }( ...$parameters );
	}

	/**
	 * Refresh the model attributes.
	 *
	 * @return static|null Model instance or null if not found.
	 */
	public function refresh() {
		if ( ! $this->get( 'id' ) ) {
			return null;
		}

		$instance = static::find( $this->get( 'id' ) );
		if ( ! $instance ) {
			return null;
		}

		$this->exists = true;
		$this->set_raw_attributes( $instance->get_raw_attributes() );
		return $this;
	}

	/**
	 * Create an instance of a model from another.
	 *
	 * @param Model $instance Instance to clone.
	 * @return static
	 */
	public static function instance( Model $instance ) {
		return tap(
			( new static() )->set_raw_attributes( $instance->get_raw_attributes() ),
			fn ( Model $model ) => $model->exists = true,
		);
	}

	/**
	 * Create a new instance of the model from an existing record in the database.
	 *
	 * @param array $attributes Attributes to set.
	 * @return static
	 */
	public static function new_from_existing( array $attributes ) {
		$instance = new static( [] );

		$instance->exists = true;

		return $instance->set_raw_attributes( $attributes );
	}

	/**
	 * Get an attribute model.
	 *
	 * @param string $attribute Attribute name.
	 * @return mixed
	 */
	public function get( string $attribute ) {
		if ( static::has_attribute_alias( $attribute ) ) {
			$attribute = static::get_attribute_alias( $attribute );
		}

		return $this->get_attribute( $attribute );
	}

	/**
	 * Set an attribute model.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to set.
	 */
	public function set( string $attribute, $value ) {
		if ( static::has_attribute_alias( $attribute ) ) {
			$attribute = static::get_attribute_alias( $attribute );
		}

		$this->set_attribute( $attribute, $value );
	}

	/**
	 * Check if the model needs to be booted and if so, do it.
	 */
	public static function boot_if_not_booted() {
		if ( ! isset( static::$booted[ static::class ] ) ) {
			static::boot_traits();
			static::boot();

			static::$booted[ static::class ] = true;
		}
	}

	/**
	 * Clear the list of booted models so they will be re-booted.
	 *
	 * @return void
	 */
	public static function clear_booted_models(): void {
		static::$booted = [];
	}

	/**
	 * Boot all of the bootable traits on the model.
	 */
	protected static function boot_traits() {
		$class  = static::class;
		$booted = [];

		static::$trait_initializers[ $class ] = [];

		foreach ( class_uses_recursive( $class ) as $trait ) {
			$trait_method = strtolower( class_basename( $trait ) );
			$method       = 'boot_' . $trait_method;

			if ( method_exists( $class, $method ) && ! in_array( $method, $booted ) ) {
				forward_static_call( [ $class, $method ] );

				$booted[] = $method;
			}

			$method = 'initialize_' . $trait_method;

			if ( method_exists( $class, $method ) ) {
				static::$trait_initializers[ $class ][] = $method;

				static::$trait_initializers[ $class ] = array_unique(
					static::$trait_initializers[ $class ]
				);
			}
		}
	}

	/**
	 * Initialize any initializable traits on the model.
	 *
	 * @return void
	 */
	protected function initialize_traits() {
		foreach ( static::$trait_initializers[ static::class ] as $method ) {
			$this->{ $method }();
		}
	}

	/**
	 * Bootstrap the model.
	 *
	 * Model booting is performed the first time a model is used in a request.
	 */
	protected static function boot() { }

	/**
	 * Infer the object type for the model.
	 *
	 * @return string|null
	 */
	public static function get_object_name(): ?string {
		// Use the model's object name if it exists.
		if ( ! empty( static::$object_name ) ) {
			return (string) static::$object_name;
		}

		// Infer the object name from the model name.
		$parts = explode( '\\', get_called_class() );
		return str_replace( '__', '_', Str::snake( array_pop( $parts ) ) );
	}

	/**
	 * Check if an offset exists.
	 *
	 * @param string $offset Array offset.
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return null !== $this->get( $offset );
	}

	/**
	 * Get data by the offset.
	 *
	 * @param string $offset Array offset.
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->get( $offset );
	}

	/**
	 * Set data by offset.
	 *
	 * @param string $offset Offset name.
	 * @param mixed  $value Value to set.
	 */
	public function offsetSet( $offset, $value ) {
		return $this->set( $offset, $value );
	}

	/**
	 * Unset data by offset.
	 *
	 * @param string $offset Offset to unset.
	 */
	public function offsetUnset( $offset ) {
		$this->set( $offset, null );
		unset( $this->relations[ $offset ] );
	}

	/**
	 * Magic Method to get Attributes
	 *
	 * @param string $offset Attribute to get.
	 */
	public function __get( $offset ) {
		return $this->get( $offset );
	}

	/**
	 * Magic Method to set Attributes.
	 *
	 * @param string $offset Attribute to get.
	 * @param mixed  $value Value to set.
	 */
	public function __set( $offset, $value ) {
		$this->set( $offset, $value );
	}

	/**
	 * Create a new query instance.
	 *
	 * @return Builder
	 */
	public static function query(): Builder {
		return ( new static() )->new_query();
	}

	/**
	 * Begin a query with eager loading.
	 *
	 * @param string ...$relations Relations to eager load.
	 * @return Builder
	 */
	public static function with( ...$relations ): Builder {
		return static::query()->with( ...$relations );
	}

	/**
	 * Begin a query without eager loading relationships.
	 *
	 * @param string ...$relations Relations to not eager load.
	 * @return Builder
	 */
	public static function without( ...$relations ): Builder {
		return static::query()->without( ...$relations );
	}

	/**
	 * Create a new query instance.
	 *
	 * @return Builder
	 * @throws Model_Exception Thrown for an unknown query builder for the model.
	 */
	public function new_query(): Builder {
		$builder = static::get_query_builder_class();

		if ( empty( $builder ) ) {
			throw new Model_Exception( 'Unknown query builder for model: ' . get_called_class() );
		}

		return $this->register_global_scopes(
			new $builder( get_called_class() )
		)
			->with( ...$this->with );
	}

	/**
	 * Register the global scopes for this builder instance.
	 *
	 * @param Builder $builder Builder instance.
	 * @return Builder
	 */
	public function register_global_scopes( Builder $builder ) {
		foreach ( $this->get_global_scopes() as $identifier => $scope ) {
			$builder->with_global_scope( $identifier, $scope );
		}

		return $builder;
	}

	/**
	 * Handle dynamic method calls into the model.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Method parameters.
	 * @return mixed
	 */
	public function __call( string $method, array $parameters ) {
		return $this->forward_call_to( $this->new_query(), $method, $parameters );
	}

	/**
	 * Handle dynamic static method calls into the model.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Method parameters.
	 * @return mixed
	 */
	public static function __callStatic( string $method, array $parameters ) {
		return ( new static() )->$method( ...$parameters );
	}

	/**
	 * Get the primary key for the model.
	 *
	 * @return string
	 */
	public function get_key_name(): string {
		return $this->primary_key;
	}

	/**
	 * Get the default foreign key name for the model.
	 *
	 * @return string
	 */
	public function get_foreign_key(): string {
		return Str::snake( str_replace( '_', '', class_basename( $this ) ) ) . '_' . $this->get_key_name();
	}

	/**
	 * Get the value of the model's route key.
	 *
	 * @return mixed
	 */
	public function get_route_key() {
		return $this->get_attribute( $this->get_route_key_name() );
	}

	/**
	 * Get the route key for models.
	 *
	 * @return string
	 */
	public function get_route_key_name(): string {
		return 'slug';
	}

	/**
	 * Get the registerable route for the model. By default this is set relative
	 * the object's archive route with the object's slug.
	 *
	 *     /object_name/object_slug/
	 *
	 * @return string|null
	 */
	public static function get_route(): ?string {
		return static::get_archive_route() . '/{slug}';
	}

	/**
	 * Get the registerable archive route for the model. By default this is set to
	 * the object's name:
	 *
	 *     /object_name/
	 *
	 * @return string|null
	 */
	public static function get_archive_route(): ?string {
		return '/' . static::get_object_name();
	}

	/**
	 * Retrieve the model for a bound value.
	 *
	 * @param mixed       $value Value to compare against.
	 * @param string|null $field Field to compare against.
	 * @return static|null
	 */
	public function resolve_route_binding( $value, $field = null ) {
		$key = $field ?? $this->get_route_key_name();

		// If the key is the same as the primary key, use the find method to help with some caching.
		if ( $key === $this->primary_key ) {
			return static::find( $value );
		}

		return static::query()->where( $key, $value )->first();
	}

	/**
	 * Get all the models from the database.
	 *
	 * @return Collection
	 */
	public static function all(): Collection {
		return static::query()->take( -1 )->get();
	}

	/**
	 * Create a new instance of a model and save it.
	 *
	 * @param array $args Model arguments.
	 * @return static
	 */
	public static function create( array $args ) {
		$instance = new static();
		$instance->save( $args );
		$instance->refresh();
		return $instance;
	}

	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return $this->attributes_to_array();
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param int $options json_encode() options.
	 * @return string
	 */
	public function to_json( $options = 0 ): string {
		return wp_json_encode( $this->to_array(), $options );
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
