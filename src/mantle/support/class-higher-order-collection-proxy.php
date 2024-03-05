<?php
/**
 * Higher_Order_Collection_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

/**
 * Higher Order Collection Proxy
 *
 * @mixin Enumerable
 */
class Higher_Order_Collection_Proxy {

	/**
	 * The collection being operated on.
	 *
	 * @var Enumerable
	 */
	protected $collection;

	/**
	 * Create a new proxy instance.
	 *
	 * @param  string     $method
	 * @return void
	 */
 public function __construct( Enumerable $collection, /**
	 * The method being proxied.
	 */
 protected $method ) {
		$this->collection = $collection;
	}

	/**
	 * Proxy accessing an attribute onto the collection items.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->collection->{ $this->method }(
			fn($value) => is_array( $value ) ? $value[ $key ] : $value->{$key}
		);
	}

	/**
	 * Proxy a method call onto the collection items.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		return $this->collection->{ $this->method }(
			fn($value) => $value->{ $method }( ...$parameters )
		);
	}
}
