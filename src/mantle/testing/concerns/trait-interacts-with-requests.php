<?php
/**
 * Interacts_With_Requests trait file.
 *
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Closure;
use Mantle\Http_Client\Request;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use Mantle\Testing\Mock_Http_Response;
use Mantle\Testing\Mock_Http_Sequence;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\value;

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
	 * Storage of request URLs.
	 *
	 * @var Collection
	 */
	protected $recorded_requests;

	/**
	 * Flag to prevent external requests from being made.
	 *
	 * @var Mock_Http_Response|\Closure|bool
	 */
	protected $preventing_stray_requests = false;

	/**
	 * Setup the trait.
	 */
	public function interacts_with_requests_set_up() {
		$this->stub_callbacks    = collect();
		$this->recorded_requests = collect();

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
	 *
	 * @throws RuntimeException If the request was made without a matching faked request.
	 */
	public function pre_http_request( $preempt, $request_args, $url ) {
		$this->recorded_requests[] = new Request( $request_args, $url );

		if ( ! $this->stub_callbacks->is_empty() ) {
			foreach ( $this->stub_callbacks as $callback ) {
				$response = $callback( $url, $request_args );
				if ( $response instanceof Mock_Http_Response ) {
					return $response->to_array();
				}

				if ( ! is_null( $response ) ) {
					return $response;
				}
			}
		}

		if ( false !== $this->preventing_stray_requests ) {
			$prevent = value( $this->preventing_stray_requests );

			if ( $prevent instanceof Mock_Http_Response ) {
				return $prevent->to_array();
			}

			throw new RuntimeException( "Attempted request to [{$url}] without a matching fake." );
		}

		// To aid in debugging, print a message to the console that this test is making an actual HTTP request
		// which it probably shouldn't be.
		printf(
			'No faked HTTP response found, making an actual HTTP request. [%s]',
			esc_url( $url )
		) . PHP_EOL;

		return $preempt;
	}

	/**
	 * Fake a remote request.
	 *
	 * A response object could be passed with a matching URL to fake. Also supports passing
	 * a closure that will be invoked when the HTTP request is made. The closure will be passed
	 * the request URL and request arguments to determine if it wishes to make a response. For more
	 * information on how this is used, see the `create_stub_request_callback()` method below and the
	 * relevant test for the trait (Mantle\Tests\Testing\Concerns\Test_Interacts_With_Requests).
	 *
	 * @throws \InvalidArgumentException Thrown on invalid argument.
	 *
	 * @param Closure|string|array                  $url_or_callback URL to fake, array of URL and response pairs, or a closure
	 *                                                               that will return a faked response.
	 * @param Mock_Http_Response|Mock_Http_Sequence $response Optional response object, defaults to creating a 200 response.
	 * @return static|Mock_Http_Response
	 */
	public function fake_request( $url_or_callback = null, $response = null ) {
		if ( is_array( $url_or_callback ) ) {
			$this->stub_callbacks = $this->stub_callbacks->merge(
				collect( $url_or_callback )
					->map(
						function( $response, $url_or_callback ) {
							return $this->create_stub_request_callback( $url_or_callback, $response );
						}
					)
			);

			return $this;
		}

		// Allow a callback to be passed instead.
		if ( is_callable( $url_or_callback ) ) {
			$this->stub_callbacks->push( $url_or_callback );
			return $this;
		}

		// Throw an exception on an unknown argument.
		if ( ! is_string( $url_or_callback ) && ! is_null( $url_or_callback ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Expected a URL string or a callback, got %s.',
					gettype( $url_or_callback )
				)
			);
		}

		// Renaming for clarity.
		$url = $url_or_callback ?? '*';

		// If no arguments passed, assume that all requests should return an 200 response.
		if ( is_null( $response ) ) {
			$response = new Mock_Http_Response();
		}

		$this->stub_callbacks->push( $this->create_stub_request_callback( $url, $response ) );

		return $response;
	}

	/**
	 * Prevent stray external requests.
	 *
	 * @param Mock_Http_Response|\Closure|bool $response A default response or callback to use, boolean otherwise.
	 */
	public function prevent_stray_requests( $response = true ) {
		$this->preventing_stray_requests = $response;
	}

	/**
	 * Allow stray external requests.
	 */
	public function allow_stray_requests() {
		$this->preventing_stray_requests = false;
	}

	/**
	 * Retrieve a callback for the stubbed response.
	 *
	 * @param string                                $url URL to stub.
	 * @param Mock_Http_Response|Mock_Http_Sequence $response Response to send.
	 * @return Closure
	 */
	protected function create_stub_request_callback( string $url, $response ): Closure {
		return function( string $request_url, array $request_args ) use ( $url, $response ) {
			if ( ! Str::is( Str::start( $url, '*' ), $request_url ) ) {
				return;
			}

			return $response instanceof Closure || $response instanceof Mock_Http_Sequence
				? $response( $request_url, $request_args )
				: $response;
		};
	}

	/**
	 * Get a collection of the request pairs matching the given truth test.
	 *
	 * @param callable $callback Callback to invoke on each request.
	 * @return Collection
	 */
	protected function recorded_requests( callable $callback ): Collection {
		if ( empty( $this->recorded_requests ) ) {
				return collect();
		}

		$callback = $callback ?: fn () => true;

		return collect( $this->recorded_requests )->filter( fn ( Request $response ) => $callback( $response ) );
	}

	/**
	 * Assert that a request was sent.
	 *
	 * @param string|callable $url_or_callback Specific URL to check against or a callback to
	 *                                         check against specific request information.
	 * @param int             $expected_times Number of times the request should have been
	 *                                        sent, optional.
	 */
	public function assertRequestSent( $url_or_callback = null, int $expected_times = null ) {
		if ( is_null( $url_or_callback ) ) {
			return PHPUnit::assertTrue( $this->recorded_requests->is_not_empty(), 'A request was made.' );
		}

		if ( is_string( $url_or_callback ) ) {
			$url_or_callback = fn ( $request ) => Str::is( $url_or_callback, $request->url() );
		}

		$requests = $this->recorded_requests( $url_or_callback )->count();

		PHPUnit::assertTrue(
			$requests > 0,
			'An expected request was not recorded.',
		);

		if ( null !== $expected_times ) {
			PHPUnit::assertEquals( $expected_times, $requests, 'Expected request count does not match.' );
		}
	}

	/**
	 * Assert that a request was not sent.
	 *
	 * @param string|callable $url_or_callback URL to check against or callback.
	 */
	public function assertRequestNotSent( $url_or_callback = null ) {
		if ( is_string( $url_or_callback ) ) {
			$url_or_callback = fn ( $request ) => Str::is( $url_or_callback, $request->url() );
		}

		PHPUnit::assertEquals(
			0,
			$this->recorded_requests( $url_or_callback )->count(),
			'Unexpected request was recorded.',
		);
	}

	/**
	 * Assert that no request was sent.
	 *
	 * @return void
	 */
	public function assertNoRequestSent() {
		PHPUnit::assertEmpty(
			$this->recorded_requests,
			'Requests were recorded',
		);
	}

	/**
	 * Assert a specific request count was sent.
	 *
	 * @param int $count Request count.
	 * @return void
	 */
	public function assertRequestCount( int $count ) {
		PHPUnit::assertCount( $count, $this->recorded_requests );
	}
}
