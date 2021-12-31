<?php
/**
 * Pending_Request class file
 *
 * @package Mantle
 */

namespace Mantle\Http\Client;

use function Mantle\Framework\Helpers\tap;

/**
 * Pending HTTP Request
 */
class Pending_Request {
	/**
	 * URL for the request.
	 *
	 * @var string
	 */
	protected string $url;

	/**
	 * Options for the request.
	 *
	 * @var array
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
	 *
	 * @var array
	 */
	protected array $pending_files = [];

	/**
	 * Body format.
	 *
	 * @var string
	 */
	protected string $body_format;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->as_json();
	}

	/**
	 * Indicate the request contains form parameters.
	 *
	 * @return static
	 */
	public function as_form() {
		return $this
			->body_format( 'form' )
			->content_type( 'application/x-www-form-urlencoded' );
	}

	/**
	 * Indicate the request contains JSON.
	 *
	 * @return static
	 */
	public function as_json() {
		return $this
			->body_format( 'json' )
			->content_type( 'application/json' );
	}

	/**
	 * Attach a raw body to the request.
	 *
	 * @param  string $content Content to attach.
	 * @param  string $content_type Content mime type.
	 * @return static
	 */
	public function with_body( $content, $content_type ) {
		$this->body_format( 'body' );

		$this->pending_body = $content;

		$this->content_type( $content_type );

		return $this;
	}

	/**
	 * Pass raw options to the request (passed to `wp_remote_request()`).
	 *
	 * @param array $options Options for the request.
	 * @return static
	 */
	public function with_options( array $options ) {
		$this->options['options'] = $options;
		return $this;
	}

	/**
	 * Indicate the request is a multi-part form request.
	 *
	 * @return static
	 */
	public function as_multipart() {
		return $this->body_format( 'multipart' );
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
	 * @return static
	 */
	public function content_type( string $content_type ) {
		return $this->with_headers( [ 'Content-Type' => $content_type ] );
	}

	/**
	 * Indicate that JSON should be returned by the server.
	 *
	 * @return static
	 */
	public function accept_json() {
		return $this->accept( 'application/json' );
	}

	/**
	 * Indicate the type of content that should be returned by the server.
	 *
	 * @param  string $content_type Content type.
	 * @return static
	 */
	public function accept( $content_type ) {
		return $this->with_headers( [ 'Accept' => $content_type ] );
	}

	/**
	 * Add the given headers to the request.
	 *
	 * @param  array $headers Headers to add.
	 * @return static
	 */
	public function with_headers( array $headers ) {
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
	 * @return static
	 */
	public function with_header( string $key, $value ) {
		return $this->with_headers( [ $key => $value ] );
	}

	/**
	 * Specify the basic authentication username and password for the request.
	 *
	 * @param string $username Username.
	 * @param string $password Password.
	 * @return static
	 */
	public function with_basic_auth( string $username, string $password ) {
		return $this->with_header(
			'Authorization',
			'Basic ' . base64_encode( $username . ':' . $password )
		);
	}

	/**
	 * Specify the digest authentication username and password for the request.
	 *
	 * @param  string $username
	 * @param  string $password
	 * @return static
	 */
	public function with_digest_auth( $username, $password ) {
		return tap(
			$this,
			function ( $request ) use ( $username, $password ) {
				return $this->options['auth'] = [ $username, $password, 'digest' ];
			}
		);
	}

	/**
	 * Specify an authorization token for the request.
	 *
	 * @param  string $token
	 * @param  string $type
	 * @return static
	 */
	public function with_token( $token, $type = 'Bearer' ) {
		return $this->with_header( 'Authorization', trim( $type . ' ' . $token ) );
	}

	/**
	 * Specify the user agent for the request.
	 *
	 * @param  string $user_agent User agent to set.
	 * @return static
	 */
	public function with_user_agent( $user_agent ) {
		$this->options['user-agent'] = $user_agent;
		return $this;
	}

	/**
	 * Specify the cookies that should be included with the request.
	 *
	 * @param  \WP_Http_Cookie[] $cookies Cookies to pass.
	 * @return static
	 */
	public function with_cookies( array $cookies ) {
		$this->options['cookies'] = $cookies;

		return $this;
	}

	/**
	 * Specify a single cookie that should be included with the request.
	 *
	 * @param \WP_Http_Cookie $cookie Cookie to include.
	 * @return static
	 */
	public function with_cookie( \WP_Http_Cookie $cookie ) {
		return $this->with_cookies( [ $cookie ] );
	}

	/**
	 * Indicate that redirects should not be followed.
	 *
	 * @return static
	 */
	public function without_redirecting() {
		return tap(
			$this,
			function ( $request ) {
				return $this->options['allow_redirects'] = false;
			}
		);
	}

	/**
	 * Indicate that redirects should be followed.
	 *
	 * @param int $times Number of redirects to allow.
	 * @return static
	 */
	public function with_redirecting( int $times = 5 ) {
		return tap(
			$this,
			function ( $request ) use ( $times ) {
				return $this->options['allow_redirects'] = $times;
			}
		);
	}

	/**
	 * Indicate that TLS certificates should not be verified.
	 *
	 * @return static
	 */
	public function without_verifying() {
		return tap(
			$this,
			function ( $request ) {
				return $this->options['verify'] = false;
			}
		);
	}

	/**
	 * Specify the timeout (in seconds) for the request.
	 *
	 * @param  int $seconds
	 * @return static
	 */
	public function timeout( int $seconds ) {
		return tap(
			$this,
			function () use ( $seconds ) {
				$this->options['timeout'] = $seconds;
			}
		);
	}

	/**
	 * Number of times to retry a failed request.
	 *
	 * @param int $retry Number of retries.
	 * @return static
	 */
	public function retry( int $retry ) {
		$this->options['retry'] = $retry;
		return $this;
	}

	/**
	 * Issue a GET request to the given URL.
	 *
	 * @param  string            $url
	 * @param  array|string|null $query
	 * @return Response
	 */
	public function get( string $url, $query = null ): Response {
		return $this->send(
			'GET',
			$url,
			[
				'query' => $query,
			]
		);
	}

	/**
	 * Issue a HEAD request to the given URL.
	 *
	 * @param  string            $url
	 * @param  array|string|null $query
	 * @return Response
	 */
	public function head( string $url, $query = null ): Response {
		return $this->send(
			'HEAD',
			$url,
			[
				'query' => $query,
			]
		);
	}

	/**
	 * Issue a POST request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response
	 */
	public function post( string $url, array $data = [] ): Response {
		return $this->send(
			'POST',
			$url,
			[
				$this->body_format => $data,
			]
		);
	}

	/**
	 * Issue a PATCH request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response
	 */
	public function patch( $url, $data = [] ): Response {
		return $this->send(
			'PATCH',
			$url,
			[
				$this->body_format => $data,
			]
		);
	}

	/**
	 * Issue a PUT request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response
	 */
	public function put( $url, $data = [] ): Response {
		return $this->send(
			'PUT',
			$url,
			[
				$this->body_format => $data,
			]
		);
	}

	/**
	 * Issue a DELETE request to the given URL.
	 *
	 * @param  string $url
	 * @param  array  $data
	 * @return Response
	 */
	public function delete( $url, $data = [] ): Response {
		return $this->send(
			'DELETE',
			$url,
			empty( $data ) ? [] : [
				$this->body_format => $data,
			]
		);
	}

	/**
	 * Issue a request to the given URL.
	 *
	 * @param  string $method HTTP Method.
	 * @param  string $url URL for the request.
	 * @param  array  $options Options for the request.
	 * @return Response
	 */
	public function send( string $method, string $url, array $options = [] ): Response {
		$this->url     = $url;
		$this->options = array_merge( $this->options, $options );

		$this->prepare_request_url();

		$args = $this->get_request_args( $method );

		return retry(
			min( 1, $this->options['retry'] ?? 1 ),
			function() use ( $args ) {
				$response = new Response( wp_remote_request( $this->url, $args ) );

				if ( ! $response->successful() ) {
					throw new Http_Client_Exception();
				}

				return $response;
			},
			$this->options['retry_delay'] ?? 0,
		);
	}

	/**
	 * Parse multi-part form data.
	 *
	 * @param  array $data
	 * @return array|array[]
	 */
	protected function parse_multipart_body_format( array $data ) {
		return collect( $data )->map(
			function ( $value, $key ) {
				return is_array( $value ) ? $value : [
					'name'     => $key,
					'contents' => $value,
				];
			}
		)->values()->all();
	}

	/**
	 * Prepare the request URL.
	 *
	 * @return void
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
	 *
	 * @param string $method Request method.
	 * @return array
	 */
	protected function get_request_args( string $method ): array {
		if ( isset( $this->options[ $this->body_format ] ) ) {
			if ( 'multipart' === $this->body_format ) {
					$this->options[ $this->body_format ] = $this->parse_multipart_body_format( $this->options[ $this->body_format ] );
			} elseif ( 'body' === $this->body_format ) {
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
			'method'      => $method,
			'redirection' => $this->options['allow_redirects'],
			'sslverify'   => $this->options['verify'] ?? true,
			'timeout'     => $this->options['timeout'] ?? 5,
		];

		switch ( $this->body_format ) {
			case 'json':
				$args['body'] = wp_json_encode( $this->options[ $this->body_format ] );
				break;
			default:
				$args['body'] = $this->options[ $this->body_format ];
				break;
		}

		return array_merge( $args, $this->options['options'] ?? [] );
	}
}
