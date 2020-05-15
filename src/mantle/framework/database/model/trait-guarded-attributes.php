<?php
/**
 * Guarded_Attributes class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

/**
 * Guard Specific Attributes from being set.
 */
trait Guarded_Attributes {
	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [];

	/**
	 * Flag if the model is being guarded.
	 *
	 * @var bool
	 */
	protected $guarded = true;

	/**
	 * Check if the model is guarded.
	 *
	 * @return bool
	 */
	public function is_model_guarded(): bool {
		return $this->guarded;
	}

	/**
	 * Set if a model is or is not being guarded.
	 *
	 * @param bool $guarded Flag if the model is being guarded.
	 */
	public function set_model_guard( bool $guarded ) {
		$this->guarded = $guarded;
	}

	/**
	 * Check if a model attribute is guarded.
	 *
	 * @param string $attribute Attribute to check.
	 * @return bool
	 */
	public function is_guarded( string $attribute ): bool {
		if ( ! $this->guarded ) {
			return false;
		}

		return in_array( $attribute, $this->guarded_attributes, true );
	}

	/**
	 * Check if the model is currently guarded.
	 *
	 * @param bool $guarded Flag if the model is guarded.
	 */
	public function guard( bool $guarded ) {
		$this->guarded = $guarded;
	}
}
