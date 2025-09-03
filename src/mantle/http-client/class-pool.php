<?php
/**
 * Pool class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use WP_Error;

use function Alley\WP\Concurrent_Remote_Requests\wp_remote_request;

/**
 * Http Pool for making requests concurrently.
 *
 * @mixin \Mantle\Http_Client\Pooled_Pending_Request
 */
class Pool {
	/**
	 * Pool of pending requests.
	 *
	 * @var array<string|int, Pooled_Pending_Request>
	 */
	protected array $pool = [];

	/**
	 * Constructor.
	 *
	 * @param Pending_Request|Pooled_Pending_Request $base_request
	 */
	public function __construct( protected Pending_Request|Pooled_Pending_Request $base_request ) {
	}

	/**
	 * Create a pending request for the pool
	 */
	protected function create_request(): Pooled_Pending_Request {
		if ( ! $this->base_request instanceof Pooled_Pending_Request ) {
			return Pooled_Pending_Request::from_pending_request( $this->base_request );
		}

		return $this->base_request;
	}

	/**
	 * Retrieve the requests for the given pool
	 *
	 * @throws Http_Client_Exception Thrown in error in response from wp_remote_request().
	 * @return array<int|string, Response>
	 */
	public function results(): array {
		$results = wp_remote_request(
			array_map(
				function ( Pooled_Pending_Request $request ): array {
					$request->prepare_request();

					return [ $request->url(), $request->get_request_args() ];
				},
				$this->pool,
			)
		);

		if ( is_wp_error( $results ) ) {
			throw new Http_Client_Exception( Response::create( $results ) );
		}

		return array_map( fn ( array|WP_Error $result ) => Response::create( $result ), $results );
	}

	/**
	 * Call a pending request a specific index name.
	 *
	 * @param string $key The name of the pending request.
	 */
	public function as( string $key ): Pooled_Pending_Request {
		$this->pool[ $key ] = $this->create_request();

		return $this->pool[ $key ];
	}

	/**
	 * Add a request to the pool with a numeric index.
	 *
	 * @param string       $method Method name.
	 * @param array<mixed> $args   Arguments for the method.
	 */
	public function __call( string $method, array $args = [] ): Pooled_Pending_Request {
		$request = $this->create_request()->{$method}( ...$args );

		$this->pool[] = $request;

		return $request;
	}
}
