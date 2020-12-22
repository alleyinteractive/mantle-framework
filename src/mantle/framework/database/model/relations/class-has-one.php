<?php
/**
 * Has_One class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

/**
 * Has One Relationship
 */
class Has_One extends Has_One_Or_Many {
	/**
	 * Get the results of the relationship.
	 *
	 * @return \Mantle\Framework\Database\Model\Model|null
	 */
	public function get_results() {
		$this->add_constraints();

		return ! is_null( $this->parent ) && $this->parent->exists
			? $this->query->first()
			: null;
	}
}
