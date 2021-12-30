<?php
namespace Mantle\Http\Client;

use function Mantle\Framework\Helpers\tap;

class Pending_Request {
	protected string $url;

	protected array $options = [];

	protected $pending_body;

	protected array $pending_files = [];

	/**
	 * Body format.
	 *
	 * @var string
	 */
	protected string $body_format;

	public function __construct() {
		$this->as_json();
	}

	public function as_form() {
		return $this
			->body_format( 'form' )
			->content_type( 'application/x-www-form-urlencoded' );
	}

	public function as_json() {
		return $this
			->body_format( 'json' )
			->content_type( 'application/json' );
	}

	/**
	 * Attach a raw body to the request.
	 *
	 * @param  string  $content
	 * @param  string  $contentType
	 * @return $this
	 */
	public function with_body($content, $contentType)
	{
			$this->body_format('body');

			$this->pending_body = $content;

			$this->content_type($contentType);

			return $this;
	}

	public function with_options( array $options ) {
		$this->options = array_merge_recursive( $this->options, $options );
		return $this;
	}

	/**
	 * Indicate the request is a multi-part form request.
	 *
	 * @return $this
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
	 * @param  string $contentType
	 * @return $this
	 */
	public function content_type( string $contentType ) {
		return $this->with_headers( [ 'Content-Type' => $contentType ] );
	}

	/**
	 * Indicate that JSON should be returned by the server.
	 *
	 * @return $this
	 */
	public function accept_json() {
		return $this->accept( 'application/json' );
	}

	/**
	 * Indicate the type of content that should be returned by the server.
	 *
	 * @param  string $contentType
	 * @return $this
	 */
	public function accept( $contentType ) {
		return $this->with_headers( [ 'Accept' => $contentType ] );
	}

	/**
	 * Add the given headers to the request.
	 *
	 * @param  array $headers
	 * @return $this
	 */
	public function with_headers( array $headers ) {
		return tap(
			$this,
			function ( $request ) use ( $headers ) {
				return $this->options = array_merge_recursive(
					$this->options,
					[
						'headers' => $headers,
					]
				);
			}
		);
	}

	/**
	 * Specify the basic authentication username and password for the request.
	 *
	 * @param  string $username
	 * @param  string $password
	 * @return $this
	 */
	public function with_basic_auth( string $username, string $password ) {
		return tap(
			$this,
			function ( $request ) use ( $username, $password ) {
				return $this->options['auth'] = [ $username, $password ];
			}
		);
	}

	/**
	 * Specify the digest authentication username and password for the request.
	 *
	 * @param  string $username
	 * @param  string $password
	 * @return $this
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
	 * @return $this
	 */
	public function with_token( $token, $type = 'Bearer' ) {
		return tap(
			$this,
			function ( $request ) use ( $token, $type ) {
				return $this->options['headers']['Authorization'] = trim( $type . ' ' . $token );
			}
		);
	}

	/**
	 * Specify the user agent for the request.
	 *
	 * @param  string $userAgent
	 * @return $this
	 */
	public function with_user_agent( $userAgent ) {
		return tap(
			$this,
			function ( $request ) use ( $userAgent ) {
				return $this->options['headers']['User-Agent'] = trim( $userAgent );
			}
		);
	}

	/**
	 * Specify the cookies that should be included with the request.
	 *
	 * @param  array  $cookies Cookies to pass.
	 * @return $this
	 */
	public function with_cookies( array $cookies ) {
		$this->options['cookies'] = $cookies;

		return $this;
	}

	/**
	 * Indicate that redirects should not be followed.
	 *
	 * @return $this
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
	 * @return $this
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
	 * @return $this
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
	 * @return $this
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
	 * Issue a GET request to the given URL.
	 *
	 * @param  string            $url
	 * @param  array|string|null $query
	 * @return \Illuminate\Http\Client\Response
	 */
	public function get( string $url, $query = null ) {
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
	 * @return \Illuminate\Http\Client\Response
	 */
	public function head( string $url, $query = null ) {
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
	 * @return \Illuminate\Http\Client\Response
	 */
	public function post( string $url, array $data = [] ) {
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
	 * @return \Illuminate\Http\Client\Response
	 */
	public function patch( $url, $data = [] ) {
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
	 * @return \Illuminate\Http\Client\Response
	 */
	public function put( $url, $data = [] ) {
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
	 * @return \Illuminate\Http\Client\Response
	 */
	public function delete( $url, $data = [] ) {
		return $this->send(
			'DELETE',
			$url,
			empty( $data ) ? [] : [
				$this->body_format => $data,
			]
		);
	}

	public function send( string $method, string $url, array $options = [] ) {
		$this->url     = $url;
		$this->options = array_merge( $this->options, $options );

		$this->prepare_request_url();

		$args = $this->get_request_args( $method );

		return retry( 1, function() use ( $args ){
			$request = wp_remote_request( $this->url, $args );

			return new Response( $request );
		} );
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
				// $this->url = $this->url =
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
			if ( $this->body_format === 'multipart' ) {
					$this->options[ $this->body_format ] = $this->parse_multipart_body_format( $this->options[ $this->body_format ] );
			} elseif ( $this->body_format === 'body' ) {
					$this->options[ $this->body_format ] = $this->pendingBody;
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
				$args['body'] = json_encode( $this->options[ $this->body_format ] );
				break;
			default:
				$args['body'] = $this->options[ $this->body_format ];
				break;
		}

		return $args;
	}
}
