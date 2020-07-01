<?php
namespace Mantle\Framework\Database\Model;

use SML\Base_REST_Field;
use SML\REST_Field_Schema;
use Closure;
use SML\REST_Field_Get_Callback;
use SML\REST_Field_Update_Callback;

use function SML\default_from_rest_schema;
use function SML\fill_rest_schema;

class Rest_Field implements REST_Field_Schema, REST_Field_Get_Callback, REST_Field_Update_Callback {
	/**
	 * Attribute name.
	 *
	 * @var string
	 */
	protected $attribute = '';

	/**
	 * @var Closure
	 */
	protected $get_callback = false;

	/**
	 * @var Closure
	 */
	protected $update_callback = false;

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
}
