<?php
/**
 * Registered_REST_Field class file
 *
 * @package SML
 */

namespace Mantle\REST_API;

use function Mantle\Support\Helpers\backtickit;
use function Mantle\Support\Helpers\get_callable_fqn;

/**
 * A representation of a registered REST API field.
 *
 * @see register_rest_field().
 *
 * @method mixed get_callback( ...$args )
 * @method mixed update_callback( ...$args )
 */
class Registered_REST_Field {
	/**
	 * Object the field is registered to. Fields can be registered to many
	 * objects but are stored per-type and retrieved here that way.
	 *
	 * @var string
	 */
	public $object_type = '';

	/**
	 * Attribute name.
	 *
	 * @var string
	 */
	public $attribute = '';

	/**
	 * Attribute schema.
	 *
	 * @var array
	 */
	public $schema;

	/**
	 * Callback function used to retrieve the field value.
	 *
	 * @var ?callable
	 */
	public $get_callback;

	/**
	 * Callback function used to set and update the field value.
	 *
	 * @var ?callable
	 */
	public $update_callback;

	/**
	 * Constructor.
	 *
	 * @param string $object_type Object type.
	 * @param string $attribute   Attribute name.
	 * @param array  $args        Additional registration arguments.
	 */
	private function __construct( string $object_type, string $attribute, array $args ) {
		$this->object_type = $object_type;
		$this->attribute   = $attribute;

		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Generate an instance from the global store of registered fields.
	 *
	 * @param string $object_type Object type.
	 * @param string $attribute   Attribute name.
	 * @return self Generated instance.
	 */
	public static function get_instance( string $object_type, string $attribute ): ?self {
		global $wp_rest_additional_fields;

		if ( empty( $wp_rest_additional_fields[ $object_type ][ $attribute ] ) ) {
			return null;
		}

		return new static( $object_type, $attribute, $wp_rest_additional_fields[ $object_type ][ $attribute ] );
	}

	/**
	 * Convenience for accessing callable arguments.
	 *
	 * @throws \BadMethodCallException For unimplemented or invalid callbacks.
	 *
	 * @param string $name      Name of the method being called.
	 * @param array  $arguments Enumerated array containing the parameters passed to $name.
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( 'get_callback' === $name && \is_callable( $this->get_callback ) ) {
			return \call_user_func_array( $this->get_callback, $arguments );
		}

		if ( 'update_callback' === $name && \is_callable( $this->update_callback ) ) {
			return \call_user_func_array( $this->update_callback, $arguments );
		}

		throw new \BadMethodCallException(
			\sprintf(
				/* translators: 1: field attribute name, 2: method name */
				\__( 'Registered REST API field %1$s does not implement %2$s.', 'mantle' ),
				backtickit( $this->attribute ),
				backtickit( get_callable_fqn( $name ) )
			)
		);
	}
}
