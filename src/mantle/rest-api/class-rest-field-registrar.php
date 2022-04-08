<?php
/**
 * REST_Field_Registrar class file
 *
 * @package SML
 */

namespace Mantle\REST_API;

use Mantle\Contracts\REST_API\REST_Field;
use Mantle\Contracts\Rest_Api\REST_Field_Get_Callback;
use Mantle\Contracts\REST_API\REST_Field_Schema;
use Mantle\Contracts\REST_API\REST_Field_Update_Callback;

use function Mantle\Support\Helpers\backtickit;

/**
 * Registers REST fields that implement library interfaces.
 */
class REST_Field_Registrar {
	/**
	 * Register a field that extends the library REST_Field class.
	 *
	 * If the field implements the get or update interfaces, the field
	 * will be registered with those callbacks.
	 *
	 * @throws \BadMethodCallException For unmet requirements.
	 *
	 * @param REST_Field $field The field definition.
	 */
	public function register( REST_Field $field ) {
		$object_types = $field->get_object_types();

		if ( ! $object_types ) {
			throw new \BadMethodCallException( \__( 'Please define the object types.', 'mantle' ) );
		}

		$attribute = $field->get_attribute();

		if ( ! $attribute ) {
			throw new \BadMethodCallException( \__( 'Please define the attribute name.', 'mantle' ) );
		}

		$args = [];

		if ( $field instanceof REST_Field_Schema ) {
			$args['schema'] = $field->get_schema();
		}

		if ( $field instanceof REST_Field_Get_Callback ) {
			$args['get_callback'] = [ $field, 'get_callback' ];
		}

		if ( $field instanceof REST_Field_Update_Callback ) {
			$args['update_callback'] = [ $field, 'update_callback' ];
		}

		\register_rest_field( $object_types, $attribute, $args );
	}

	/**
	 * Register a field only if the attribute hasn't been registered to any object types.
	 *
	 * @throws \LogicException If the field can't be registered.
	 *
	 * @param REST_Field $field The field definition.
	 */
	public function register_once( REST_Field $field ) {
		$attribute = $field->get_attribute();

		foreach ( (array) $field->get_object_types() as $object_type ) {
			if ( Registered_REST_Field::get_instance( $object_type, $attribute ) ) {
				throw new \LogicException(
					\sprintf(
						/* translators: 1: attribute, 2: object type */
						\__( 'Attribute %1$s is already registered to object type %2$s.', 'mantle' ),
						backtickit( $attribute ),
						backtickit( $object_type )
					)
				);
			}
		}

		$this->register( $field );
	}
}
