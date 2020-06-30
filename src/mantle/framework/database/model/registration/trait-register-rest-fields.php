<?php
/**
 * Register_Post_Type trait file.
 *
 * @package Mantle
 * @phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
 */

namespace Mantle\Framework\Database\Model\Registration;

use Closure;
use Mantle\Framework\Database\Model\Model_Exception;
use SML\REST_Field;
use SML\REST_Field_Registrar;

/**
 * Model Trait to allow a post type to be registered for a model.
 */
trait Register_Rest_Fields {
	/**
	 * REST Field Registar
	 *
	 * @var REST_Field_Registrar
	 */
	protected static $rest_registrar;

	/**
	 * REST API Fields for the model.
	 *
	 * @var array
	 */
	protected static $rest_fields = [];

	/**
	 * Register the post type.
	 */
	public static function boot_Register_Rest_Fields() {
		static::$rest_registrar = new REST_Field_Registrar();

		\add_action( 'rest_api_init', [ __CLASS__, 'register_post_type' ] );
	}

	/**
	 * Register the fields for the model.
	 *
	 * @throws Model_Exception Thrown when registering a post type that is already registered.
	 */
	public static function register_fields() {
		if ( ! isset( static::$rest_registrar ) ) {
			$class = get_called_class();
			throw new Model_Exception( "REST field registrar is not defined for [${class}]" );
		}

		array_map( [ static::$rest_registrar, 'register' ], static::$rest_fields );
	}

	/**
	 * Register a REST API field.
	 *
	 * @param REST_Field|string $field Field instance/field attribute to register.
	 * @param Closure           $get_callback Callback for the field if $field isn't a field.
	 * @return REST_Field
	 */
	public static function register_field( $field, Closure $get_callback = null ) {
		// Bail early if the field is a valid REST Field.
		if ( $field instanceof REST_Field ) {
			static::$rest_fields[] = $field;
			return $field;
		}



		if ( $field instanceof Closure ) {

		}
	}
}
