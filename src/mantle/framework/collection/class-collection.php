<?php
/**
 * Collection class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Collection;

use Mantle\Framework\Support\Arr;
use ArrayAccess;

class Collection implements ArrayAccess {

	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $items = [];

	/**
	 * Create a new collection.
	 *
	 * @param	 mixed	$items
	 * @return void
	 */
	public function __construct($items = []) {
		$this->items = $items;
	}

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function all() {
		return $this->items;
	}

	/**
	 * Get the first item from the collection passing the given truth test.
	 *
	 * @param	 callable|null	$callback
	 * @param	 mixed	$default
	 * @return mixed
	 */
	public function first(callable $callback = null, $default = null) {
		return Arr::first($this->items, $callback, $default);
	}

	/**
	 * Get the last item from the collection passing the given truth test.
	 *
	 * @param	 callable|null	$callback
	 * @param	 mixed	$default
	 * @return mixed
	 */
	public function last( callable $callback = null, $default = null ) {
		return Arr::last( $this->items, $callback, $default );
	}

	/**
	 * Run a map over each of the items.
	 *
	 * @param	 callable	 $callback
	 * @return static
	 */
	public function map(callable $callback) {
		$keys = array_keys($this->items);

		$items = array_map($callback, $this->items, $keys);

		return new static(array_combine($keys, $items));
	}

	public function each( callable $callback = null ) {
		foreach ($this as $key => $item) {
			if ($callback($item, $key) === false) {
				break;
			}
		}

		return $this;
	}

	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return static
	 */
	public function values() {
		return new static(array_values($this->items));
	}

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