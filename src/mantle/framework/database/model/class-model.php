<?php
/**
 * Model class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use ArrayAccess;

/**
 * Database Model
 *
 * @todo Add Json-able, arrayable, serialize interfaces
 */
abstract class Model implements ArrayAccess {
	use Attributes;

	/**
	 * Model aliases.
	 *
	 * @var string[]
	 */
	protected static $aliases = [];

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object ) {
		$this->attributes = (array) $object;
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param int $object_id Object ID.
	 * @return Model|null
	 */
	abstract public static function find( $object_id ): ?Model;

	/**
	 * Get am attribute model.
	 *
	 * @param string $attribute Attribute name.
	 * @return mixed
	 */
	public function get( string $attribute ) {
		if ( isset( static::$aliases[ $attribute ] ) ) {
			$attribute = static::$aliases[ $attribute ];
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
		if ( isset( static::$aliases[ $attribute ] ) ) {
			$attribute = static::$aliases[ $attribute ];
		}

		$this->set_attribute( $attribute, $value );
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
	 * Magic Method for Attributes
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
}
