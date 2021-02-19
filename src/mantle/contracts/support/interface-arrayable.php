<?php
/**
 * Arrayable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Support;

interface Arrayable {
	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function to_array();
}
