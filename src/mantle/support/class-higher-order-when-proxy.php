<?php
/**
 * Higher_Order_When_Proxy class file
 *
 * @package Mantle
 */

namespace Mantle\Support;

/**
 * Higher Order When Proxy
 *
 * Allow a higher-order proxy that can be used conditionally.
 */
class Higher_Order_When_Proxy {

	/**
	 * Create a new proxy instance.
	 *
	 * @param mixed $target The target being conditionally operated on.
	 * @param bool  $condition The condition for proxying.
	 */
	public function __construct( protected mixed $target, protected $condition ) {}

	/**
	 * Proxy accessing an attribute onto the target.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->condition
			? $this->target->{$key}
			: $this->target;
	}

	/**
	 * Proxy a method call on the target.
	 *
	 * @param  string       $method
	 * @param  array<mixed> $parameters
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		return $this->condition
			? $this->target->{$method}( ...$parameters )
			: $this->target;
	}
}
