<?php
/**
 * Collection class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Collection;

use ArrayAccess;

class Collection implements ArrayAccess {

	/**
	 * Determine if a given offset exists.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->items[$offset] );
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->items[$offset];
	}

	/**
	 * Set the value at a given offset.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet( mixed $offset , mixed $value ): void {
		if ( is_null( $offset ) ) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	/**
	 * Unset the value at a given offset.
	 *
	 * @param  string $key
	 * @return void
	 */
	public function offsetUnset( mixed $offset ): void {
		unset( $this->items[$offset] );
	}

}