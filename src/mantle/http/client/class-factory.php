<?php
/**
 * Factory class file
 *
 * @package Mantle
 */

namespace Mantle\Http\Client;

use Mantle\Support\Traits\Macroable;

/**
 * Http Client Factory
 */
class Factory {
	use Macroable {
		__call as macro_call;
	}

	/**
	 * Create a new pending request.
	 *
	 * @return Pending_Request
	 */
	protected function new_pending_request(): Pending_Request {
		return new Pending_Request( $this );
	}

	/**
	 * Execute a method against a new pending request instance.
	 *
	 * @param string $method Http Method.
	 * @param array  $parameters Request parameters.
	 * @return Pending_Request
	 */
	public function __call( string $method, array $parameters ) {
		if ( static::hasMacro( $method ) ) {
			return $this->macro_call( $method, $parameters );
		}

		return $this->new_pending_request()->{$method}( ...$parameters );
	}
}
