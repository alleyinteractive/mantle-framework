<?php
namespace Mantle\Http\Client;

use Mantle\Support\Traits\Macroable;

class Factory {
	use Macroable {
		__call as macro_call;
	}

	protected function new_pending_request(): Pending_Request {
		return new Pending_Request( $this );
	}

	public function __call( string $method, array $parameters ) {
		if ( static::hasMacro( $method ) ) {
			return $this->macro_call( $method, $parameters );
		}

		return $this->new_pending_request()->{$method}( ...$parameters );
	}
}
