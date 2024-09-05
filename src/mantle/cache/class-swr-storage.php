<?php
/**
 * SWR_Storage class file
 *
 * @package Mantle
 */

namespace Mantle\Cache;

/**
 * Cache SWR (stale-while-revalidate) DTO object.
 */
class SWR_Storage implements \Stringable {
	/**
	 * Constructor.
	 *
	 * @param mixed $value Value.
	 * @param int   $stale_time Stale time.
	 */
	public function __construct( public readonly mixed $value, public readonly int $stale_time ) {}

	/**
	 * Convert to string.
	 */
	public function __toString(): string {
		return (string) $this->value;
	}
}
