<?php
/**
 * Has_Many class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Support\Collection;

/**
 * Has Many Relationship
 */
class Has_Many extends Has_One_Or_Many {
	/**
	 * Get the results of the relationship.
	 *
	 * @return \Mantle\Framework\Support\Collection
	 */
	public function get_results() {
		$this->add_constraints();

		return ! is_null( $this->parent ) && $this->parent->exists
			? $this->query->get()
			: new Collection();
	}
}
