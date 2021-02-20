<?php
/**
 * Register_Taxonomy trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Registration;

use Mantle\Contracts\Database\Registrable as Registrable_Contract;
use Mantle\Database\Model\Concerns\Custom_Term_Link;
use Mantle\Database\Model\Model_Exception;

/**
 * Model Trait to allow a taxonomy to be registered for a model.
 */
trait Register_Taxonomy {
	use Custom_Term_Link;

	/**
	 * Register the taxonomy.
	 */
	public static function boot_register_taxonomy() {
		\add_action( 'init', [ __CLASS__, 'register_object' ] );
	}

	/**
	 * Register the taxonomy for the model.
	 *
	 * @throws Model_Exception Thrown when registering a taxonomy that is already registered.
	 */
	public static function register_object() {
		$taxonomy = static::get_object_name();

		if ( \taxonomy_exists( $taxonomy ) ) {
			throw new Model_Exception( 'Unable to register taxonomy (taxonomy already exists): ' . $taxonomy );
		}

		\register_taxonomy( $taxonomy, static::get_taxonomy_object_types(), static::get_registration_args() );
	}

	/**
	 * Get the object types for the model.
	 * Supports WordPress object names or the Mantle Model class name.
	 *
	 * @return string[]
	 * @throws Model_Exception Thrown on invalid class name being passed to object types.
	 */
	protected static function get_taxonomy_object_types(): array {
		$object_types = (array) ( static::$object_types ?? [] ); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass

		foreach ( $object_types as &$object_type ) {
			// Detect a class name being used.
			if ( false !== strpos( $object_type, '\\' ) ) {
				// Ensure the class name uses the Registrable Contract.
				if ( ! in_array( Registrable_Contract::class, class_implements( $object_type ), true ) ) {
					throw new Model_Exception( 'Unknown object type class provided: ' . $object_type );
				}

				// Convert the object type to the object's registration name.
				$object_type = $object_type::get_registration_name();
			}
		}

		return $object_types;
	}

	/**
	 * Add taxonomy to object type.
	 *
	 * @param string $object_type Object type to add.
	 */
	public function add_to_object_type( string $object_type ) {
		\register_taxonomy_for_object_type( static::get_registration_name(), $object_type );
	}
}
