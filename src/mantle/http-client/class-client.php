<?php
namespace Mantle\Http_Client;

use InvalidArgumentException;
use Mantle\Contracts\Http_Client\Client as Contract;
use Mantle\Support\Pipeline;

use function Mantle\Support\Helpers\retry;

class Client implements Contract {
	public static function create(): static {
		return new static();
	}

	/**
	 * Constructor.
	 *
	 * @param Pending_Client_Request $base_request The pending request instance.
	 */
	public function __construct( protected Pending_Client_Request $base_request = new Pending_Client_Request() ) {}

	/**
	 * Send a GET request.
	 *
	 * @param string            $url The request URL.
	 * @param array|string|null $query Optional query parameters.
	 * @return Response
	 */
	public function get( string $url, array|string|null $query = null ): Response {
		$request = $this->new_request()->method( Http_Method::GET )->url( $url );

		if ( ! is_null( $query ) ) {
			$request->with_query( $query );
		}

		return $this->send( $request );
	}

	/**
	 * Send a HEAD request.
	 *
	 * @param string            $url The request URL.
	 * @param array|string|null $query Optional query parameters.
	 * @return Response
	 */
	public function head( string $url, array|string|null $query = null ): Response {
		$request = $this->new_request()->method( Http_Method::HEAD )->url( $url );

		if ( ! is_null( $query ) ) {
			$request->with_query( $query );
		}

		return $this->send( $request );
	}

	/**
	 * Send a POST request.
	 *
	 * @param string     $url The request URL.
	 * @param array|null $data Optional request body data.
	 * @return Response
	 */
	public function post( string $url, ?array $data = null ): Response {
		$request = $this->new_request()->method( Http_Method::POST )->url( $url );

		return $this->send( $request, is_null( $data ) ? [] : [ $request->body_format => $data ] );
	}

	/**
	 * Send a PATCH request.
	 *
	 * @param string     $url The request URL.
	 * @param array|null $data Optional request body data.
	 * @return Response
	 */
	public function patch( string $url, ?array $data = null ): Response {
		$request = $this->new_request()->method( Http_Method::PATCH )->url( $url );

		return $this->send( $request, is_null( $data ) ? [] : [ $request->body_format => $data ] );
	}

	/**
	 * Send a PUT request.
	 *
	 * @param string     $url The request URL.
	 * @param array|null $data Optional request body data.
	 * @return Response
	 */
	public function put( string $url, ?array $data = null ): Response {
		$request = $this->new_request()->method( Http_Method::PUT )->url( $url );

		return $this->send( $request, is_null( $data ) ? [] : [ $request->body_format => $data ] );
	}

	/**
	 * Send a DELETE request.
	 *
	 * @param string     $url The request URL.
	 * @param array|null $data Optional request body data.
	 * @return Response
	 */
	public function delete( string $url, ?array $data = null ): Response {
		$request = $this->new_request()->method( Http_Method::DELETE )->url( $url );

		return $this->send( $request, is_null( $data ) ? [] : [ $request->body_format => $data ] );
	}

	/**
	 * Send the prepared request.
	 *
	 * @param Pending_Client_Request $request The request to send.
	 * @param array                  $options Additional options for the request.
	 * @return Response
	 * @throws InvalidArgumentException If no URL is provided.
	 * @throws Http_Client_Exception On failed request and retry conditions.
	 */
	public function send( Pending_Client_Request $request, array $options = [] ): Response {
		$request->with_options( $options )->prepare_request();
		dump($request);

		if ( ! $request->url() ) {
			throw new InvalidArgumentException(
				'You must provide a URL for the request. Use the `url()` method to set it.'
			);
		}

		dump($request->options(), $request->url());

		return retry(
			$request->option( 'retry', 1 ),
			function ( int $attempts ) use ( $request ) {
				$response = ( new Pipeline() )
					->send( $this )
					->through( $request->middleware )
					->then(
						fn () => Response::create(
							wp_remote_request(
								$request->url(),
								$request->get_request_args(),
							),
						),
					);

				assert( $response instanceof Response, 'Expected response to be an instance of Response.' );

				// Throw the exception if the request is being retried (so it can be
				// retried) or if configured to always throw the exception.
				if (
					! $response->successful()
					&& (
						$request->option( 'throw_exception', false )
						|| $attempts < $request->option( 'retry', 0 )
					)
				) {
					throw new Http_Client_Exception( $response );
				}

				return $response;
			},
			$request->option( 'retry_delay', 0 ),
		);
	}

	/**
	 * Create a new pending request instance.
	 *
	 * @return Pending_Client_Request
	 */
	protected function new_request(): Pending_Client_Request {
		return clone $this->base_request;
	}
}
