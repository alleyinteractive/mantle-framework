<?php
/**
 * Model_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Meta;

use Mantle\Framework\Database\Model\Model_Exception;

/**
 * Interface for interfacing with a model's meta.
 */
trait Model_Meta {
	/**
	 * Meta queued for saving.
	 *
	 * @var [type]
	 */
	protected $queued_meta = [];

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
		if ( ! $this->id() ) {
			$this->queue_meta_attribute( $meta_key, $meta_value );
			return;
		}

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
	 * Retrieve the meta 'attribute'.
	 *
	 * @return Model_Meta_Proxy
	 */
	public function get_meta_attribute() {
		return new Model_Meta_Proxy( $this );
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
			$this->queued_meta[ $key ] = $value;
			$this->set_meta( $key, $value );
		}
	}

	/**
	 * Get a queued meta attribute.
	 *
	 * @param string $key Meta key.
	 * @return mixed|null Meta value or null.
	 */
	public function get_queued_meta_attribute( string $key ) {
		return $this->queued_meta[ $key ] ?? null;
	}

	/**
	 * Queue a meta attribute for saving.
	 * Allows meta to be set before a model is saved.
	 *
	 * Should not be called directly, only to be used via `$model->meta->...`.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 */
	public function queue_meta_attribute( string $key, $value ) {
		$this->queued_meta[ $key ] = $value;
	}

	/**
	 * Store queued model meta.
	 */
	protected function store_queued_meta() {
		foreach ( $this->queued_meta as $key => $value ) {
			$this->set_meta( $key, $value );
		}

		$this->queued_meta = [];
	}
}
