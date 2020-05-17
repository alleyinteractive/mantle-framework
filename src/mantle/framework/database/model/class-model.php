<?php
/**
 * Model class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use ArrayAccess;
use Mantle\Framework\Database\Query\Builder;
use Mantle\Framework\Support\Forward_Calls;
use Mantle\Framework\Support\Str;

/**
 * Database Model
 *
 * @todo Add Json-able, arrayable, serialize interfaces
 */
abstract class Model implements ArrayAccess {
	use Aliases,
		Attributes,
		Forward_Calls,
		Relationships;

	/**
	 * The array of booted models.
	 *
	 * @var array
	 */
	protected static $booted = [];

	/**
	 * An object's registerable name (post type, taxonomy, etc.).
	 *
	 * @var string
	 */
	public static $object_name;

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		static::boot_if_not_booted();

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
	 * Query builder class to use.
	 *
	 * @return string
	 */
	abstract public static function get_query_builder_class(): string;

	/**
	 * Refresh the model attributes.
	 *
	 * @return static
	 */
	public function refresh() {
		if ( ! $this->get( 'id' ) ) {
			return;
		}

		$instance = static::find( $this->get( 'id' ) );
		$this->set_raw_attributes( $instance->get_raw_attributes() );
		return $this;
	}

	/**
	 * Create a new instance of the model from an existing record in the database.
	 *
	 * @param array $attributes Attributes to set.
	 * @return static
	 */
	public static function new_from_existing( array $attributes ) {
		$instance = new static( [] );

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
	 *
	 * @return void
	 */
	protected function boot_if_not_booted() {
		if ( ! isset( static::$booted[ static::class ] ) ) {
			static::boot();
			static::$booted[ static::class ] = true;
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
	 * Create a new query instance.
	 *
	 * @todo Add global scopes for queries.
	 *
	 * @return Builder
	 */
	public function new_query(): Builder {
		$builder = static::get_query_builder_class();

		return new $builder( get_called_class() );
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
}
