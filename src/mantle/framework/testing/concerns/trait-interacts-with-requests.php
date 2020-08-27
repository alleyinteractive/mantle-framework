<?php
/**
 * Interacts_With_Requests trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Concerns;

use Closure;
use Mantle\Framework\Support\Collection;
use Mantle\Framework\Support\Str;
use Mantle\Framework\Testing\Mock_Http_Response;

use function Mantle\Framework\Helpers\collect;

/**
 * Allow Mock HTTP Requests
 */
trait Interacts_With_Requests {
	/**
	 * Storage of the callbacks to mock the requests.
	 *
	 * @var Collection
	 */
	protected $stub_callbacks;

	/**
	 * Setup the trait.
	 */
	public function interacts_with_requests_set_up() {
		$this->stub_callbacks = collect();

		\add_filter( 'pre_http_request', [ $this, 'pre_http_request' ], PHP_INT_MAX, 3 );
	}

	/**
	 * Remove the filter to intercept the request.
	 */
	public function interacts_with_requests_tear_down() {
		\remove_filter( 'pre_http_request', [ $this, 'pre_http_request' ], PHP_INT_MAX );
	}

	/**
	 * Filters pre_http_request to intercept the request, mock a response, and
	 * return it. If the response has already been preempted, the preempt will
	 * be returned instead. Regardless, this object unhooks itself from the
	 * pre_http_request filter.
	 *
	 * @param false|array|\WP_Error $preempt      Whether to preempt an HTTP request's return value. Default false.
	 * @param array                 $request_args HTTP request arguments.
	 * @param string                $url          The request URL.
	 * @return mixed Array if the request has been preempted, any value that's
	 *               not false otherwise.
	 */
	public function pre_http_request( $preempt, $request_args, $url ) {
		if ( $this->stub_callbacks->is_empty() ) {
			return $preempt;
		}

		foreach ( $this->stub_callbacks as $callback ) {
			$response = $callback( $url, $request_args );
			if ( $response instanceof Mock_Http_Response ) {
				return $response->to_array();
			}

			if ( ! is_null( $response ) ) {
				return $response;
			}
		}

		// To aid in debugging, print a message to the console that this test is making an actual HTTP request
		// which it probably shouldn't be.
		echo "No faked HTTP response found, making an actual HTTP request. [{$url}]";

		return $preempt;
	}

	/**
	 * Fake a remote request.
	 *
	 * A response object could be passed with a matching URL to fake. Also supports passing
	 * a closure that will be invoked when the HTTP request is made. The closure will be passed
	 * the request URL and request arguments to determine if it wishes to make a response. For more
	 * information on how this is used, see the `get_stub_request_callback()` method below and the
	 * relevant test for the trait (Mantle\Tests\Framework\Testing\Concerns\Test_Interacts_With_Requests).
	 *
	 * @param string|array       $url URL to fake, array of URL and response pairs, or a closure
	 *                                that will return a faked response.
	 * @param Mock_Http_Response $response Optional response object, defaults to creating a 200 response.
	 * @return static|Mock_Http_Response
	 */
	public function fake_request( $url = null, Mock_Http_Response $response = null ) {
		if ( is_array( $url ) ) {
			$this->stub_callbacks = $this->stub_callbacks->merge(
				collect( $url )
					->map(
						function( $response, $url ) {
							return $this->get_stub_request_callback( $url, $response );
						}
					)
			);

			return $this;
		}

		// Allow a callback to be passed instead.
		if ( $url instanceof Closure ) {
			$this->stub_callbacks->push( $url );
			return $this;
		}

		// If no arguments passed, assume that all requests should return an 200 response.
		if ( is_null( $response ) ) {
			$response = new Mock_Http_Response();
		}

		// If no URL was passed assume that it should match all requests.
		if ( is_null( $url ) ) {
			$url = '*';
		}

		$this->stub_callbacks->push( $this->get_stub_request_callback( $url, $response ) );

		return $response;
	}

	/**
	 * Retrieve a callback for the stubbed response.
	 *
	 * @param string             $url URL to stub.
	 * @param Mock_Http_Response $response Response to send.
	 * @return Closure
	 */
	protected function get_stub_request_callback( string $url, Mock_Http_Response $response ): Closure {
		return function( string $request_url, array $request_args ) use ( $url, $response ) {
			if ( ! Str::is( Str::start( $url, '*' ), $request_url ) ) {
				return;
			}

			return $response instanceof Closure
				? $response( $request_url, $request_args )
				: $response;
		};
	}
}
