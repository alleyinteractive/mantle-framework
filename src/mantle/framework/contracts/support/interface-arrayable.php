<?php
/**
 * Arrayable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Support;

interface Arrayable {
	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function to_array();
}
