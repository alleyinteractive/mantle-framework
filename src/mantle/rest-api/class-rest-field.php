<?php
/**
 * Rest_Field class file.
 *
 * @package Mantle
 */

namespace Mantle\REST_API;

use Closure;
use Mantle\Contracts\REST_API\REST_Field as REST_Field_Contract;
use Mantle\Contracts\Rest_Api\REST_Field_Get_Callback;
use Mantle\Contracts\REST_API\REST_Field_Schema;
use Mantle\Contracts\REST_API\REST_Field_Update_Callback;

use function Mantle\Support\Helpers\default_from_rest_schema;
use function Mantle\Support\Helpers\fill_rest_schema;

/**
 * WordPress REST API Field
 *
 * Allows a fluent registration interface for a field in the REST API.
 */
class REST_Field implements REST_Field_Contract, REST_Field_Schema, REST_Field_Get_Callback, REST_Field_Update_Callback {
	/**
	 * Attribute name.
	 *
	 * @var string
	 */
	protected $attribute = '';

	/**
	 * Object types for the field.
	 *
	 * @var string[]
	 */
	protected $object_types = [];

	/**
	 * Field description.
	 *
	 * @var string
	 */
	protected $description = 'A Mantle-powered field.';

	/**
	 * Callback for the field.
	 *
	 * @var Closure|string
	 */
	protected $get_callback;

	/**
	 * Update callback for the field.
	 *
	 * @var Closure|string|null
	 */
	protected $update_callback = null;

	/**
	 * Constructor.
	 *
	 * @param string[]            $object_types Object types for the field.
	 * @param string              $attribute Attribute for the field.
	 * @param Closure|string      $callback Get callback, required.
	 * @param Closure|string|null $update_callback Update callback, optional.
	 */
	public function __construct( array $object_types, string $attribute, $callback, $update_callback = null ) {
		$this->set_object_types( $object_types );
		$this->set_attribute( $attribute );
		$this->set_callback( $callback );

		if ( null !== $update_callback ) {
			$this->set_update_callback( $update_callback );
		}
	}

	/**
	 * Get the object(s) the field is being registered to.
	 *
	 * @return array Object types for the field.
	 */
	public function get_object_types(): array {
		return $this->object_types;
	}

	/**
	 * Get the attribute name.
	 *
	 * @param string[] $object_types Object types for the field.
	 * @return static
	 */
	public function set_object_types( array $object_types ) {
		$this->object_types = $object_types;
		return $this;
	}

	/**
	 * Get the attribute name.
	 *
	 * @return string Attribute name.
	 */
	public function get_attribute(): string {
		return $this->attribute;
	}

	/**
	 * Get the attribute name.
	 *
	 * @param string $attribute Attribute name.
	 * @return static
	 */
	public function set_attribute( string $attribute ) {
		$this->attribute = $attribute;
		return $this;
	}

	/**
	 * The callback function used to retrieve the field value.
	 *
	 * @param array            $object      REST API object.
	 * @param string           $field_name  Field name.
	 * @param \WP_REST_Request $request     REST API request.
	 * @param string           $object_type Object type.
	 * @return mixed Field value.
	 */
	public function get_callback( $object, string $field_name, \WP_REST_Request $request, string $object_type ) {
		$callback = $this->get_callback;
		return $callback( $object, $field_name, $request, $object_type );
	}

	/**
	 * The callback function used to update the field value.
	 *
	 * @param mixed            $value       Submitted field value.
	 * @param mixed            $object      REST API data object.
	 * @param string           $field_name  Field name.
	 * @param \WP_REST_Request $request     REST API request.
	 * @param string           $object_type Object type.
	 * @return mixed True on success, \WP_Error object if a field cannot be updated.
	 */
	public function update_callback( $value, $object, string $field_name, \WP_REST_Request $request, string $object_type ) {
		if ( ! $this->update_callback ) {
			return true;
		}

		$callback = $this->update_callback;
		return $callback( $object, $field_name, $request, $object_type );
	}

	/**
	 * Retrieve the get callback.
	 *
	 * Named 'retrieve_callback()' to avoid a collision with 'get_callback'.
	 *
	 * @return Closure|string
	 */
	public function retrieve_callback() {
		return $this->get_callback;
	}

	/**
	 * Set the get callback.
	 *
	 * @param Closure|string $callback Callback to set.
	 * @return static
	 */
	public function set_callback( $callback ) {
		$this->get_callback = $callback;
		return $this;
	}

	/**
	 * Retrieve the set callback.
	 *
	 * @return Closure|string
	 */
	public function get_update_callback() {
		return $this->update_callback;
	}

	/**
	 * Set the update callback.
	 *
	 * @param Closure|string $callback Callback to set.
	 * @return static
	 */
	public function set_update_callback( $callback ) {
		$this->update_callback = $callback;
		return $this;
	}

	/**
	 * Get the field description.
	 *
	 * @return string Field description.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get the field description.
	 *
	 * @param string $description Field description.
	 * @return static
	 */
	public function set_description( string $description ) {
		$this->description = $description;
		return $this;
	}

	/**
	 * Convenience proxy for `fill_rest_schema()`.
	 *
	 * @param string|array $description Attribute description or schema array.
	 * @param array        $args        Remaining schema definition, if any.
	 * @return array Completed schema definition.
	 */
	protected function fill_schema( $description, array $args = [] ): array {
		return fill_rest_schema( $description, $args );
	}

	/**
	 * Convenience proxy for `default_from_rest_schema()`.
	 *
	 * This method is final because overridden methods would have no effect
	 * on the recursive calls within `default_from_rest_schema()`.
	 *
	 * @param array $schema Schema.
	 * @return mixed Default based on the schema.
	 */
	protected function default_from_schema( array $schema ) {
		return default_from_rest_schema( $schema );
	}

	/**
	 * Get the field schema.
	 *
	 * @return array Schema.
	 */
	public function get_schema(): array {
		$schema            = $this->fill_schema( $this->description );
		$schema['default'] = $this->default_from_schema( $schema );
		return $schema;
	}
}
