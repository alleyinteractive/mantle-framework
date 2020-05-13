<?php
/**
 * This file contains the Higher_Order_Tap_Proxy class
 *
 * @package Mantle
 */

namespace Mantle\Framework\Support;

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
	 * @param mixed $target
	 * @return void
	 */
	public function __construct( $target ) {
		$this->target = $target;
	}

	/**
	 * Dynamically pass method calls to the target.
	 *
	 * @param string $method
	 * @param array  $parameters
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		$this->target->{$method}( ...$parameters );

		return $this->target;
	}
}
