<?php
/**
 * Attributes trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

/**
 * Model Attributes
 */
trait Attributes {
	use Guarded_Attributes;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Keep track of attributes that have been modified.
	 *
	 * @var array
	 */
	protected $modified_attributes = [];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array
	 */
	protected $casts = [];

	/**
	 * The built-in, primitive cast types supported by the model.
	 *
	 * @var array
	 */
	protected static $supported_cast_types = [
		'array',
		'bool',
		'boolean',
		'double',
		'float',
		'int',
		'integer',
		'json',
		'object',
		'real',
		'string',
		'timestamp',
	];

	/**
	 * Get an attribute from the model.
	 *
	 * @param string $attribute Attribute name.
	 * @return mixed
	 */
	public function get_attribute( string $attribute ) {
		// Retrieve the attribute from the object.
		$value = $this->attributes[ $attribute ] ?? null;

		// Check if an attribute has a cast.
		if ( isset( $this->casts[ $attribute ] ) ) {
			$this->cast_attribute( $value, $this->casts[ $attribute ] );
		}

		// Pass the attribute to the mutator.
		if ( $this->has_get_mutator( $attribute ) ) {
			$value = $this->mutate_attribute( $attribute, $value );
		}

		return $value;
	}

	/**
	 * Set a model attribute.
	 *
	 * @todo Add cast support.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to set.
	 * @return static
	 *
	 * @throws Model_Exception Thrown when trying to set 'id'.
	 */
	public function set_attribute( string $attribute, $value ) {
		if ( $this->is_guarded( $attribute ) ) {
			throw new Model_Exception( "Unable to set '{$attribute} on model." );
		}

		if ( $this->has_set_mutator( $attribute ) ) {
			$value = $this->mutate_set_attribute( $attribute, $value );
		} else {
			$this->attributes[ $attribute ] = $value;
		}

		$this->modified_attributes[] = $attribute;

		return $this;
	}

	/**
	 * Set a raw attribute on the model.
	 *
	 * @param string $attribute Attribute name.
	 * @param mixed  $value Value to set.
	 * @return static
	 */
	public function set_raw_attribute( string $attribute, $value ) {
		$this->attributes[ $attribute ] = $value;

		return $this;
	}

	/**
	 * Get all model attributes.
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		$attributes = [];

		foreach ( $this->attributes as $key => $value ) {
			$attributes[ $key ] = $this->get_attribute( $key );
		}

		return $attributes;
	}

	/**
	 * Get the raw model attributes.
	 *
	 * @return array
	 */
	public function get_raw_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Get all modified attributes.
	 *
	 * @return array
	 */
	public function get_modified_attributes(): array {
		if ( empty( $this->modified_attributes ) ) {
			return [];
		}

		$attributes = [];
		foreach ( array_unique( $this->modified_attributes ) as $attribute ) {
			$attributes[ $attribute ] = $this->attributes[ $attribute ] ?? null;
		}

		return $attributes;
	}

	/**
	 * Set an array of attributes.
	 *
	 * @param array $attributes Attributes to set.
	 * @return static
	 */
	public function set_attributes( array $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$this->set( $key, $value );
		}

		return $this;
	}

	/**
	 * Set the raw attributes on the model.
	 *
	 * @param array $attributes Raw attributes to set.
	 * @return static
	 */
	public function set_raw_attributes( array $attributes ) {
		$this->attributes = $attributes;
		return $this;
	}

	/**
	 * Reset the modified attributes.
	 */
	protected function reset_modified_attributes() {
		$this->modified_attributes = [];
	}

	/**
	 * Cast an attribute to a specific value.
	 *
	 * @todo Add date, collection cast types.
	 *
	 * @param mixed  $value Attribute value.
	 * @param string $cast_type Cast type.
	 * @return mixed
	 */
	protected function cast_attribute( $value, string $cast_type ) {
		switch ( $cast_type ) {
			case 'int':
			case 'integer':
				return (int) $value;
			case 'real':
			case 'float':
			case 'double':
				return $this->from_float( $value );
			case 'string':
				return (string) $value;
			case 'bool':
			case 'boolean':
				return (bool) $value;
			case 'object':
				return $this->from_json( $value, true );
			case 'array':
			case 'json':
				return $this->from_json( $value );
		}

		return $value;
	}

	/**
	 * Decode the given float.
	 *
	 * @param  mixed $value Value to decode.
	 * @return mixed
	 */
	public function from_float( $value ) {
		switch ( (string) $value ) {
			case 'Infinity':
				return INF;
			case '-Infinity':
				return -INF;
			case 'NaN':
				return NAN;
			default:
				return (float) $value;
		}
	}

	/**
	 * Encode the given value as JSON.
	 *
	 * @param mixed $value Value to encode.
	 * @return string
	 */
	protected function as_json( $value ): string {
		return \wp_json_encode( $value );
	}

	/**
	 * Decode the given JSON back into an array or object.
	 *
	 * @param string $value Value to convert.
	 * @param bool   $as_object Flag as an object.
	 * @return mixed
	 */
	public function from_json( string $value, bool $as_object = false ) {
		return json_decode( $value, ! $as_object );
	}

	/**
	 * Get the mutator method name for an attribute.
	 *
	 * @param string $attribute Attribute name.
	 * @return string
	 */
	public function get_mutator_method_name( string $attribute ): string {
		return 'get_' . strtolower( $attribute ) . '_attribute';
	}

	/**
	 * Get the set mutator method name for an attribute.
	 *
	 * @param string $attribute Attribute name.
	 * @return string
	 */
	public function get_set_mutator_method_name( string $attribute ): string {
		return 'set_' . strtolower( $attribute ) . '_attribute';
	}

	/**
	 * Check if the attribute has a get mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @return bool
	 */
	public function has_get_mutator( string $attribute ): bool {
		return method_exists( $this, $this->get_mutator_method_name( $attribute ) );
	}

	/**
	 * Check if the attribute has a set mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @return bool
	 */
	public function has_set_mutator( string $attribute ): bool {
		return method_exists( $this, $this->get_set_mutator_method_name( $attribute ) );
	}

	/**
	 * Pass an attribute through a get mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @param mixed  $value Attribute value.
	 * @return mixed
	 */
	public function mutate_attribute( string $attribute, $value ) {
		return $this->{ $this->get_mutator_method_name( $attribute ) }( $value );
	}

	/**
	 * Pass an attribute through a set mutator.
	 *
	 * @param string $attribute Attribute to check.
	 * @param mixed  $value Attribute value.
	 * @return mixed
	 */
	public function mutate_set_attribute( string $attribute, $value ) {
		return $this->{ $this->get_set_mutator_method_name( $attribute ) }( $value );
	}
}
