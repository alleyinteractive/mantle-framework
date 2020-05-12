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
	public function offsetExists( $offset ): bool {
		return isset( $this->items[$offset] );
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function offsetGet( $offset ): mixed {
		return $this->items[$offset];
	}

	/**
	 * Set the value at a given offset.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet( $offset , $value ): void {
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
	public function offsetUnset( $offset ): void {
		unset( $this->items[$offset] );
	}

}