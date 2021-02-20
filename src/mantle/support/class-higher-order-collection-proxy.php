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
 * @link https://laravel-news.com/higher-order-messaging
 */
class Higher_Order_Collection_Proxy {

	/**
	 * The collection being operated on.
	 *
	 * @var Enumerable
	 */
	protected $collection;

	/**
	 * The method being proxied.
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * Create a new proxy instance.
	 *
	 * @param  \Illuminate\Support\Enumerable $collection
	 * @param  string                         $method
	 * @return void
	 */
	public function __construct( Enumerable $collection, $method ) {
		$this->method     = $method;
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
			function ( $value ) use ( $key ) {
				return is_array( $value ) ? $value[ $key ] : $value->{$key};
			}
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
			function ( $value ) use ( $method, $parameters ) {
				return $value->{ $method }( ...$parameters );
			}
		);
	}
}
