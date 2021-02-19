<?php
/**
 * User_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

/**
 * User Model Meta
 */
trait User_Meta {
	/**
	 * Get the meta type for the object.
	 *
	 * @return string
	 */
	public function get_meta_type(): string {
		return 'user';
	}
}
