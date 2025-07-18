<?php
/**
 * Pool class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use function Alley\WP\Concurrent_Remote_Requests\wp_remote_request;
use function Mantle\Support\Helpers\collect;

/**
 * Http Pool
 *
 * Supports making requests concurrently using the WordPress HTTP API.
 *
 * @mixin \Mantle\Http_Client\Pending_Request
 */
class Pool {
	/**
	 * Pool of pending requests.
	 *
	 * @var array<string|int, Pending_Request>
	 */
	protected array $pool = [];

	/**
	 * Constructor.
	 *
	 * @param Pending_Request $base_request
	 */
	public function __construct( protected Pending_Request $base_request ) {}

	// /**
	// * Make a GET request to the given URL with optional query parameters.
	// *
	// * @param string $url The URL to send the GET request to.
	// * @param array<string, mixed>|string|null $query Optional query parameters to include in the request.
	// */
	// public function get( string $url, array|string|null $query = null ): Pooled_Pending_Request {
	// $request = $this->create_request();

	// $request->url( $url )->method( Http_Method::GET );

	// if ( ! is_null( $query ) ) {
	// $request->with_options( [
	// 'query' => $query,
	// ] );
	// }

	// $this->pool[] = $request;

	// return $request;
	// }

	// /**
	// * Make a HEAD request to the given URL with optional query parameters.
	// *
	// * @param string $url The URL to send the HEAD request to.
	// * @param array<string, mixed>|string|null $query Optional query parameters to include in the request.
	// */
	// public function head( string $url, array|string|null $query = null ): Pooled_Pending_Request {
	// $request = $this->create_request();

	// $request->url( $url )->method( Http_Method::HEAD );

	// if ( ! is_null( $query ) ) {
	// $request->with_options( [
	// 'query' => $query,
	// ] );
	// }

	// $this->pool[] = $request;

	// return $request;
	// }

	// /**
	// * Make a POST request to the given URL with optional body data.
	// *
	// * @param string $url The URL to send the POST request to.
	// * @param array<string, mixed>|null $data Optional body data to include in the request.
	// */
	// public function post( string $url, ?array $data = null ): Pooled_Pending_Request {
	// $request = $this->create_request();

	// $request->url( $url )->method( Http_Method::POST );

	// if ( ! is_null( $data ) ) {
	// $request->with_options( [
	// $request->body_format => $data,
	// ] );
	// }

	// $this->pool[] = $request;

	// return $request;
	// }

	// /**
	// * Make a PATCH request to the given URL with optional body data.
	// *
	// * @param string $url The URL to send the PATCH request to.
	// * @param array<string, mixed>|null $data Optional body data to include in the request.
	// */
	// public function patch( string $url, ?array $data = null ): Pooled_Pending_Request {
	// $request = $this->create_request();

	// $request->url( $url )->method( Http_Method::PATCH );

	// if ( ! is_null( $data ) ) {
	// $request->with_options( [
	// $request->body_format => $data,
	// ] );
	// }

	// $this->pool[] = $request;

	// return $request;
	// }

	// /**
	// * Make a PUT request to the given URL with optional body data.
	// *
	// * @param string $url The URL to send the PUT request to.
	// * @param array<string, mixed>|null $data Optional body data to include in the request.
	// */
	// public function put( string $url, ?array $data = null ): Pooled_Pending_Request {
	// $request = $this->create_request();

	// $request->url( $url )->method( Http_Method::PUT );

	// if ( ! is_null( $data ) ) {
	// $request->with_options( [
	// $request->body_format => $data,
	// ] );
	// }

	// $this->pool[] = $request;

	// return $request;
	// }

	// /**
	// * Make a DELETE request to the given URL with optional body data.
	// *
	// * @param string $url The URL to send the DELETE request to.
	// * @param array<string, mixed>|null $data Optional body data to include in the request.
	// */
	// public function delete( string $url, ?array $data = null ): Pooled_Pending_Request {
	// $request = $this->create_request();

	// $request->url( $url )->method( Http_Method::DELETE );

	// if ( ! is_null( $data ) ) {
	// $request->with_options( [
	// $request->body_format => $data,
	// ] );
	// }

	// $this->pool[] = $request;

	// return $request;
	// }

	/**
	 * Send the pooled request.
	 *
	 * This method is intentionally left empty as the actual sending of requests
	 * will be handled in the results() method.
	 */
	public function send(): static {
		return $this;
	}

	/**
	 * Create a pending request for the pool
	 */
	protected function create_request(): Pooled_Pending_Request {
		return new Pooled_Pending_Request( $this );
	}

	/**
	 * Retrieve the requests for the given pool
	 *
	 * @throws Http_Client_Exception Thrown in error in response from wp_remote_request().
	 * @return array<int|string, Response>
	 */
	public function results(): array {
		dd( 'results', $this->pool );
		$results = wp_remote_request( collect( $this->pool )->map( function ( Pooled_Pending_Request $request ): array {
			$request->prepare_request();

			return [
				$request->url(),
				$request->get_request_args(),
			];
		} )->dd() );

		dd( 'results', $results );

		if ( is_wp_error( $results ) ) {
			throw new Http_Client_Exception( Response::create( $results ) );
		}

		return array_map(
			fn ( array $result ) => Response::create( $result ),
			$results,
		);
	}

	/**
	 * Call a pending request a specific index name.
	 *
	 * @param string $key The name of the pending request.
	 */
	public function as( string $key ): Pending_Request {
		$this->pool[ $key ] = $this->create_request();

		return $this->pool[ $key ];
	}

	/**
	 * Add a request to the pool with a numeric index.
	 *
	 * @param string       $method Method name.
	 * @param array<mixed> $args   Arguments for the method.
	 */
	public function __call( string $method, array $args = [] ): Pending_Request {
		$request = $this->create_request()->{$method}( ...$args );

		$this->pool[] = $request;

		return $request;
	}
}
