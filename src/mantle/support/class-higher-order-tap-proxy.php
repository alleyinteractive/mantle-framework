<?php
/**
 * This file contains the Higher_Order_Tap_Proxy class
 *
 * @package Mantle
 */

namespace Mantle\Support;

/**
 * Tap proxy.
 */
class Higher_Order_Tap_Proxy {
	/**
	 * The target being tapped.
	 *
	 * @var mixed
	 */
	public $target;

	/**
	 * Create a new tap proxy instance.
	 *
	 * @param mixed $target Object against which to call a method.
	 */
	public function __construct( $target ) {
		$this->target = $target;
	}

	/**
	 * Dynamically pass method calls to the target.
	 *
	 * @param string $method     Method to call.
	 * @param array  $parameters Params to provide to the method.
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		$this->target->{$method}( ...$parameters );

		return $this->target;
	}
}
