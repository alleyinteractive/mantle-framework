<?php
/**
 * Pending_Request class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use Mantle\Support\Pipeline;
use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Macroable;

use function Mantle\Support\Helpers\retry;
use function Mantle\Support\Helpers\tap;

/**
 * Pending Request to be made with the Http Client.
 */
class Pending_Request {
	use Conditionable, Macroable;

	/**
	 * Base URL for the request.
	 */
	protected string $base_url = '';

	/**
	 * Method for the request.
	 */
	public string $method;

	/**
	 * URL for the request.
	 */
	protected string $url;

	/**
	 * Options for the request.
	 */
	protected array $options = [];

	/**
	 * Pending body for the request.
	 *
	 * @var mixed
	 */
	protected $pending_body;

	/**
	 * Pending files for the request.
	 */
	protected array $pending_files = [];

	/**
	 * Body format.
	 */
	protected string $body_format;

	/**
	 * Middleware for the request.
	 */
	protected array $middleware = [];

	/**
	 * Flag if the request is for a pooled request.
	 */
	protected bool $pooled = false;

	/**
	 * Create an instance of the Http Client
	 *
	 * @return static
	 */
	public static function create() {
		return new static();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->as_json();
	}

	/**
	 * Indicate the request contains form parameters.
	 */
	public function as_form(): static {
		return $this
			->body_format( 'form' )
			->content_type( 'application/x-www-form-urlencoded' );
	}

	/**
	 * Indicate the request contains JSON.
	 */
	public function as_json(): static {
		return $this
			->body_format( 'json' )
			->content_type( 'application/json' );
	}

	/**
	 * Set the base URL for the pending request.
	 *
	 * @param string $url Base URL.
	 */
	public function base_url( string $url ): static {
		$this->base_url = $url;

		return $this;
	}

	/**
	 * Set or get the URL for the request.
	 *
	 * @param string $url URL for the request, optional.
	 * @return static|string
	 */
	public function url( string $url = null ) {
		if ( is_null( $url ) ) {
			return $this->url;
		}

		$this->url = $url;

		return $this;
	}

	/**
	 * Set or get the method for the request.
	 *
	 * @param string $method Http Method for the request, optional.
	 * @return static|string
	 */
	public function method( string $method = null ) {
		if ( is_null( $method ) ) {
			return $this->method;
		}

		$this->method = $method;

		return $this;
	}

	/**
	 * Attach a raw body to the request.
	 *
	 * @param  string $content Content to attach.
	 * @param  string $content_type Content mime type.
	 */
	public function with_body( string $content, string $content_type ): static {
		$this->body_format( 'body' );

		$this->pending_body = $content;

		$this->content_type( $content_type );

		return $this;
	}

	/**
	 * Attach JSON data to the request.
	 *
	 * @param array $data Data to attach.
	 */
	public function with_json( array $data ): static {
		$this->as_json();

		$this->options[ $this->body_format ] = $data;

		return $this;
	}

	/**
	 * Retrieve the body for the request.
	 */
	public function body(): mixed {
		return $this->options[ $this->body_format ] ?? $this->pending_body;
	}

	/**
	 * Pass raw options to the request (passed to `wp_remote_request()`).
	 *
	 * @param array $options Options for the request.
	 * @param bool  $merge Merge the options with the existing options, default true.
	 */
	public function with_options( array $options, bool $merge = true ): static {
		if ( $merge ) {
			$this->options['options'] = array_merge(
				$this->options['options'] ?? [],
				$options
			);
		} else {
			$this->options['options'] = $options;
		}

		return $this;
	}

	/**
	 * Specify the body format for the request
	 *
	 * @param string $format Body format.
	 * @return static
	 */
	public function body_format( string $format ) {
		$this->body_format = $format;
		return $this;
	}

	/**
	 * Specify the request's content type.
	 *
	 * @param string $content_type Content type.
	 */
	public function content_type( string $content_type ): static {
		return $this->with_header( 'Content-Type', $content_type, true );
	}

	/**
	 * Indicate that JSON should be returned by the server.
	 */
	public function accept_json(): static {
		return $this->accept( 'application/json' );
	}

	/**
	 * Indicate the type of content that should be returned by the server.
	 *
	 * @param  string $content_type Content type.
	 */
	public function accept( $content_type ): static {
		return $this->with_headers( [ 'Accept' => $content_type ] );
	}

	/**
	 * Add the given headers to the request.
	 *
	 * @param  array<string, mixed> $headers Headers to add.
	 */
	public function with_headers( array $headers ): static {
		$this->options = array_merge_recursive(
			$this->options,
			[
				'headers' => $headers,
			]
		);

		return $this;
	}

	/**
	 * Add a specific header to the request.
	 *
	 * @param string $key Header key.
	 * @param mixed  $value Header value.
	 * @param bool   $replace Replace the existing header, defaults to false.
	 */
	public function with_header( string $key, $value, bool $replace = false ): static {
		if ( $replace && isset( $this->options['headers'][ $key ] ) ) {
			unset( $this->options['headers'][ $key ] );
		}

		return $this->with_headers( [ $key => $value ] );
	}

	/**
	 * Retrieve the headers for the request.
	 *
	 * @return array<string, mixed>
	 */
	public function headers(): array {
		return $this->options['headers'] ?? [];
	}

	/**
	 * Clear the headers for the request.
	 */
	public function clear_headers(): static {
		$this->options['headers'] = [];

		return $this;
	}

	/**
	 * Retrieve a specific header for the request.
	 *
	 * @param  string $key Header key.
	 */
	public function header( string $key ): mixed {
		return $this->headers()[ $key ] ?? null;
	}

	/**
	 * Specify the basic authentication username and password for the request.
	 *
	 * @param string $username Username.
	 * @param string $password Password.
	 */
	public function with_basic_auth( string $username, string $password ): static {
		return $this->with_header(
			'Authorization',
			'Basic ' . base64_encode( $username . ':' . $password )
		);
	}

	/**
	 * Specify an authorization token for the request.
	 *
	 * @param  string $token
	 * @param  string $type
	 */
	public function with_token( string $token, string $type = 'Bearer' ): static {
		return $this->with_header( 'Authorization', trim( $type . ' ' . $token ) );
	}

	/**
	 * Specify the user agent for the request.
	 *
	 * @param  string $user_agent User agent to set.
	 */
	public function with_user_agent( string $user_agent ): static {
		$this->options['user-agent'] = $user_agent;
		return $this;
	}

	/**
	 * Clear the cookies included with the request.
	 */
	public function clear_cookies(): static {
		$this->options['cookies'] = [];
		return $this;
	}

	/**
	 * Specify the cookies that should be included with the request.
	 *
	 * @param  \WP_Http_Cookie[] $cookies Cookies to pass.
	 */
	public function with_cookies( array $cookies ): static {
		$this->options['cookies'] = array_merge_recursive(
			$this->options['cookies'] ?? [],
			$cookies,
		);

		return $this;
	}

	/**
	 * Specify a single cookie that should be included with the request.
	 *
	 * @param \WP_Http_Cookie $cookie Cookie to include.
	 */
	public function with_cookie( \WP_Http_Cookie $cookie ): static {
		return $this->with_cookies( [ $cookie ] );
	}

	/**
	 * Indicate that redirects should not be followed.
	 */
	public function without_redirecting(): static {
		$this->options['allow_redirects'] = false;
		return $this;
	}

	/**
	 * Indicate that redirects should be followed.
	 *
	 * @param int $times Number of redirects to allow.
	 */
	public function with_redirecting( int $times = 5 ): static {
		$this->options['allow_redirects'] = $times;
		return $this;
	}

	/**
	 * Indicate that TLS certificates should not be verified.
	 */
	public function without_verifying(): static {
		$this->options['verify'] = false;
		return $this;
	}

	/**
	 * Specify the timeout (in seconds) for the request.
	 *
	 * @param  int $seconds
	 */
	public function timeout( int $seconds ): static {
		$this->options['timeout'] = $seconds;
		return $this;
	}

	/**
	 * Add middleware for the request.
	 *
	 * @param callable $middleware Middleware to call.
	 */
	public function middleware( $middleware ): static {
		$this->middleware[] = $middleware;
		return $this;
	}

	/**
	 * Clear all middleware for the request.
	 */
	public function without_middleware(): static {
		$this->middleware = [];
		return $this;
	}

	/**
	 * Stream the response body to a file.
	 *
	 * @param string|null $file File to stream to, optional.
	 */
	public function stream( string $file = null ): static {
		return $this->with_options(
			[
				'filename' => $file,
				'stream'   => true,
			]
		);
	}

	/**
	 * Don't stream the response body to a file.
	 */
	public function dont_stream(): static {
		return $this->with_options( [ 'stream' => false ] );
	}

	/**
	 * Number of times to retry a failed request.
	 *
	 * @param int $retry Number of retries.
	 * @param int $delay Number of milliseconds to delay between retries, defaults to none.
	 */
	public function retry( int $retry, int $delay = 0 ): static {
		$this->options['retry'] = $retry;
		$this->options['delay'] = $delay;

		return $this;
	}

	/**
	 * Flag to throw an Http_Client_Exception on failure.
	 */
	public function throw_exception(): static {
		$this->options['throw_exception'] = true;
		return $this;
	}

	/**
	 * Flag to not throw an Http_Client_Exception on failure.
	 */
	public function dont_throw_exception(): static {
		$this->options['throw_exception'] = false;

		return $this;
	}

	/**
	 * Issue a GET request to the given URL.
	 *
	 * @param  string            $url URL to retrieve.
	 * @param  array|string|null $query Query parameters (assumed to be urlencoded).
	 * @return Response|static
	 */
	public function get( string $url, array|string|null $query = null ) {
		return $this->send(
			'GET',
			$url,
			! is_null( $query ) ? [ 'query' => $query ] : [],
		);
	}

	/**
	 * Issue a HEAD request to the given URL.
	 *
	 * @param  string            $url
	 * @param  array|string|null $query
	 * @return Response|static
	 */
	public function head( string $url, array|string|null $query = null ) {
		return $this->send(
			'HEAD',
			$url,
			! is_null( $query ) ? [ 'query' => $query ] : [],
		);
	}

	/**
	 * Issue a POST request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response|static
	 */
	public function post( string $url, ?array $data = null ) {
		return $this->send(
			'POST',
			$url,
			! is_null( $data ) ? [ $this->body_format => $data ] : [],
		);
	}

	/**
	 * Issue a PATCH request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response|static
	 */
	public function patch( string $url, ?array $data = null ) {
		return $this->send(
			'PATCH',
			$url,
			! is_null( $data ) ? [ $this->body_format => $data ] : [],
		);
	}

	/**
	 * Issue a PUT request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response|static
	 */
	public function put( string $url, ?array $data = null ) {
		return $this->send(
			'PUT',
			$url,
			! is_null( $data ) ? [ $this->body_format => $data ] : [],
		);
	}

	/**
	 * Issue a DELETE request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response|static
	 */
	public function delete( string $url, ?array $data = [] ) {
		return $this->send(
			'DELETE',
			$url,
			! is_null( $data ) ? [ $this->body_format => $data ] : [],
		);
	}

	/**
	 * Issue a single request to the given URL.
	 *
	 * @param  string $method HTTP Method.
	 * @param  string $url URL for the request.
	 * @param  array  $options Options for the request.
	 * @return Response|static
	 */
	public function send( string $method, string $url, array $options = [] ) {
		$this->url     = ltrim( rtrim( $this->base_url, '/' ) . '/' . ltrim( $url, '/' ), '/' );
		$this->options = array_merge( $this->options, $options );
		$this->method  = $method;

		// Ensure some options are always set.
		$this->options['throw_exception'] ??= false;
		$this->options['retry']             = max( 1, $this->options['retry'] ?? 1 );

		$this->prepare_request_url();

		// If this is a pooled request, return the instance of the request.
		if ( $this->pooled ) {
			return $this;
		}

		return retry(
			$this->options['retry'],
			function( int $attempts ) {
				$response = ( new Pipeline() )
					->send( $this )
					->through( $this->middleware )
					->then(
						fn () => Response::create(
							wp_remote_request(
								$this->url,
								$this->get_request_args(),
							),
						),
					);

				// Throw the exception if the request is being retried (so it can be
				// retried) or if configured to always throw the exception.
				if (
					! $response->successful()
					&& (
						$this->options['throw_exception']
						|| $attempts < $this->options['retry']
					)
				) {
					throw new Http_Client_Exception( $response );
				}

				return $response;
			},
			$this->options['retry_delay'] ?? 0,
		);
	}

	/**
	 * Determine if this is a pooled request.
	 *
	 * @param bool $pooled Whether this is a pooled request.
	 */
	public function pooled( bool $pooled = true ): static {
		$this->pooled = $pooled;

		return $this;
	}

	/**
	 * Create a pool request from the current pending request.
	 *
	 * @param callable $callback Callback to build the HTTP pool.
	 * @return array<int|string, Response>
	 */
	public function pool( callable $callback ): array {
		return tap( new Pool( $this ), $callback )->results();
	}

	/**
	 * Prepare the request URL.
	 */
	protected function prepare_request_url(): void {
		if ( isset( $this->options['query'] ) ) {
			if ( is_array( $this->options['query'] ) ) {
				$this->url = add_query_arg( $this->options['query'], $this->url );
			} elseif ( is_string( $this->options['query'] ) ) {
				// Append the string query string.
				$this->url = "{$this->url}?{$this->options['query']}";
			}
		}
	}

	/**
	 * Prepare the request arguments to pass to `wp_remote_request()`.
	 */
	public function get_request_args(): array {
		if ( isset( $this->options[ $this->body_format ] ) ) {
			if ( 'body' === $this->body_format ) {
				$this->options[ $this->body_format ] = $this->pending_body;
			}

			if ( is_array( $this->options[ $this->body_format ] ) ) {
				$this->options[ $this->body_format ] = array_merge(
					$this->options[ $this->body_format ],
					$this->pending_files
				);
			}
		} else {
			$this->options[ $this->body_format ] = $this->pending_body;
		}

		// Set some default arguments.
		if ( ! isset( $this->options['allow_redirects'] ) ) {
			$this->options['allow_redirects'] = true;
		} elseif ( true === $this->options['allow_redirects'] ) {
			$this->options['allow_redirects'] = 5;
		}

		$args = [
			'cookies'     => $this->options['cookies'] ?? [],
			'headers'     => $this->options['headers'] ?? [],
			'method'      => $this->method,
			'redirection' => $this->options['allow_redirects'],
			'sslverify'   => $this->options['verify'] ?? true,
			'timeout'     => $this->options['timeout'] ?? 5,
		];

		switch ( $this->body_format ) {
			case 'json':
				if ( isset( $this->options[ $this->body_format ] ) ) {
					$args['body'] = wp_json_encode( $this->options[ $this->body_format ] );
				}

				break;
			default:
				if ( isset( $this->options[ $this->body_format ] ) ) {
					$args['body'] = $this->options[ $this->body_format ];
				}

				break;
		}

		return array_merge( $args, $this->options['options'] ?? [] );
	}
}
