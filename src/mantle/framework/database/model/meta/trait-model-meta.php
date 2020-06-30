<?php
/**
 * Model_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Meta;

use Mantle\Framework\Contracts\Database\Core_Object;
use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Model\Model_Exception;

/**
 * Interface for interfacing with a model's meta.
 */
trait Model_Meta {
	/**
	 * Retrieve meta data for the object.
	 *
	 * @param string $meta_key Meta key to retrieve.
	 * @param bool   $single Return the first meta key, defaults to true.
	 * @return mixed
	 */
	public function get_meta( string $meta_key, bool $single = true ) {
		return \get_metadata( $this->get_meta_type(), $this->id(), $meta_key, $single );
	}

	/**
	 * Update meta value for the object.
	 *
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value to store.
	 * @param string $prev_value Optional, previous meta value.
	 */
	public function set_meta( string $meta_key, $meta_value, $prev_value = '' ) {
		\update_metadata( $this->get_meta_type(), $this->id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete a object's meta.
	 *
	 * @param string $meta_key Meta key to delete by.
	 * @param mixed  $meta_value Previous meta value to delete.
	 */
	public function delete_meta( string $meta_key, $meta_value = '' ) {
		\delete_metadata( $this->get_meta_type(), $this->id(), $meta_key, $meta_value );
	}

	/**
	 * Allow setting meta through an array via an attribute mutator.
	 *
	 * @param array $meta_values Meta values to set.
	 * @throws Model_Exception Thrown on invalid value being set.
	 */
	public function set_meta_attribute( $meta_values ) {
		if ( ! is_array( $meta_values ) ) {
			throw new Model_Exception( 'Attribute value passed to meta is not an array.' );
		}

		foreach ( $meta_values as $key => $value ) {
			$this->set_meta( $key, $value );
		}
	}
}
