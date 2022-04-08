<?php
/**
 * Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use function Mantle\Support\Helpers\collect;

/**
 * Base Factory
 */
abstract class Factory {
	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return mixed
	 */
	abstract public function create( array $args = [] );

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return mixed
	 */
	abstract public function get_object_by_id( int $object_id );

	/**
	 * Creates multiple objects.
	 *
	 * @param int   $count Amount of objects to create.
	 * @param array $args  Optional. The arguments for the object to create. Default is empty array.
	 *
	 * @return array
	 */
	public function create_many( int $count, array $args = [] ) {
		return collect()
			->pad( $count, null )
			->map(
				function() use ( $args ) {
					return $this->create( $args );
				}
			)
			->to_array();
	}

	/**
	 * Creates an object and returns its object.
	 *
	 * @param array $args Optional. The arguments for the object to create. Default is empty array.
	 * @return mixed The created object.
	 */
	public function create_and_get( $args = [] ) {
		$object_id = $this->create( $args );

		if ( is_wp_error( $object_id ) ) {
			return $object_id;
		}

		return $this->get_object_by_id( $object_id );
	}
}
