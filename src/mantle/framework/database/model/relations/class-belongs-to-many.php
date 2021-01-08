<?php
/**
 * Belongs_To_Many class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

/**
 * Creates a 'Belongs To Many' relationship.
 */
class Belongs_To_Many extends Belongs_To {
	/**
	 * Retrieve the results of the query.
	 *
	 * @return \Mantle\Framework\Database\Model\Model|null
	 */
	public function get_results() {
		$this->add_constraints();

		return $this->query->get();
	}
}
