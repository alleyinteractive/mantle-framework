<?php
/**
 * Model_Meta_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

use Mantle\Contracts\Database\Model_Meta;

/**
 * Allow meta to be retrieve as an attribute on the object.
 */
class Model_Meta_Proxy {
	/**
	 * Constructor.
	 *
	 * @param Model_Meta $model Model to reference.
	 */
	public function __construct( protected Model_Meta $model ) {}

	/**
	 * Retrieve model meta by key.
	 *
	 * @param string $key Meta key.
	 * @return mixed
	 */
	public function __get( string $key ) {
		$queued_value = $this->model->get_queued_meta_attribute( $key );
		if ( null !== $queued_value ) {
			return $queued_value;
		}

		return $this->model->get_meta( $key );
	}

	/**
	 * Set model meta.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 */
	public function __set( string $key, $value ) {
		$this->model->queue_meta_attribute( $key, $value );
	}

	/**
	 * Delete model meta.
	 *
	 * @param string $key Meta key.
	 */
	public function __unset( string $key ) {
		$this->model->delete_meta( $key );
	}
}
