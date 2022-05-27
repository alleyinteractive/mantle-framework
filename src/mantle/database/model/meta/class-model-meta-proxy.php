<?php
/**
 * Model_Meta_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

use Mantle\Database\Model\Model;

/**
 * Allow meta to be retrieve as an attribute on the object.
 */
class Model_Meta_Proxy {
	/**
	 * Model to retrieve meta from.
	 *
	 * @var Model
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @param Model $model Model to reference.
	 */
	public function __construct( Model $model ) {
		$this->model = $model;
	}

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
