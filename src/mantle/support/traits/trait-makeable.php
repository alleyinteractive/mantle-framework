<?php
/**
 * Makeable trait file.
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

trait Makeable {
	/**
	 * Create a new static instance from arguments.
	 *
	 * @return static
	 */
	public static function make( ...$arguments ) {
		return new static( ...$arguments );
	}
}
