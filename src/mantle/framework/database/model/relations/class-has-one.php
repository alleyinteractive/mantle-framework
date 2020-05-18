<?php
namespace Mantle\Framework\Database\Model\Relations;

class Has_One extends Has_One_Or_Many {
	/**
	 * Get the key value of the parent's local key.
	 *
	 * @return mixed
	 */
	public function get_parent_key() {
		return $this->parent->get_attribute( $this->local_key );
	}
}
