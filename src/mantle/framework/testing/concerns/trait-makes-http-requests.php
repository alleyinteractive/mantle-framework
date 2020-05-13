<?php
/**
 * This file contains the Makes_Http_Requests trait
 *
 * @package Mantle
 */

namespace Mantle\Framekwork\Testing\Concerns;

use Mantle\Framework\Support\Str;
use Mantle\Framework\Testing\Test_Response;

/**
 * Trait for Test_Case classes which want to make http-like requests against
 * WordPress.
 */
trait Makes_Http_Requests {
	/**
	 * Additional headers for the request.
	 *
	 * @var array
	 */
	protected $default_headers = [];

	/**
	 * Additional server variables for the request.
	 *
	 * @var array
	 */
	protected $server_variables = [];

	/**
	 * Indicates whether redirects should be followed.
	 *
	 * @var bool
	 */
	protected $follow_redirects = false;

	/**
	 * Define additional headers to be sent with the request.
	 *
	 * @param array $headers Headers for the request.
	 * @return $this
	 */
	public function with_headers( array $headers ) {
		$this->default_headers = array_merge( $this->default_headers, $headers );

		return $this;
	}

	/**
	 * Flush all the configured headers.
	 *
	 * @return $this
	 */
	public function flush_headers() {
		$this->default_headers = [];

		return $this;
	}

	/**
	 * Define a set of server variables to be sent with the requests.
	 *
	 * @param array $server Server variables.
	 * @return $this
	 */
	public function with_server_variables( array $server ) {
		$this->server_variables = $server;

		return $this;
	}

	/**
	 * Automatically follow any redirects returned from the response.
	 *
	 * @return $this
	 */
	public function following_redirects() {
		$this->follow_redirects = true;

		return $this;
	}

	/**
	 * Set the referer header and previous URL session value in order to simulate
	 * a previous request.
	 *
	 * @param string $url URL for the referer header.
	 * @return $this
	 */
	public function from( string $url ) {
		return $this->with_header( 'referer', $url );
	}

	/**
	 * Add a header to be sent with the request.
	 *
	 * @param string $name  Header name (key).
	 * @param string $value Header value.
	 * @return $this
	 */
	public function with_header( string $name, string $value ) {
		$this->default_headers[ $name ] = $value;

		return $this;
	}

	/**
	 * Visit the given URI with a GET request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function get( $uri, array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'GET', $uri, [], $server );
	}

	/**
	 * Transform headers array to array of $_SERVER vars with HTTP_* format.
	 *
	 * @param array $headers Headers to convert to $_SERVER vars.
	 * @return array
	 */
	protected function transform_headers_to_server_vars( array $headers ) {
		$headers           = array_merge( $this->default_headers, $headers );
		$formatted_headers = [];
		foreach ( $headers as $name => $value ) {
			$name = strtr( strtoupper( $name ), '-', '_' );

			$formatted_headers[ $this->format_server_header_key( $name ) ] = $value;
		}

		return $formatted_headers;
	}

	/**
	 * Format the header name for the server array.
	 *
	 * @param string $name Header name.
	 * @return string
	 */
	protected function format_server_header_key( $name ) {
		if ( ! Str::starts_with( $name, 'HTTP_' ) && 'CONTENT_TYPE' !== $name && 'REMOTE_ADDR' !== $name ) {
			return 'HTTP_' . $name;
		}

		return $name;
	}

	/**
	 * Call the given URI and return the Response.
	 *
	 * @param string      $method     Request method.
	 * @param string      $uri        Request URI.
	 * @param array       $parameters Request params.
	 * @param array       $server     Server vars.
	 * @param string|null $content    Request body.
	 * @return Test_Response
	 */
	public function call( $method, $uri, $parameters = [], $server = [], $content = null ) {
		$response_content = '';
		$response_status  = 200;
		$response_headers = [];
		return new Test_Response( $response_content, $response_status, $response_headers );
	}

	/**
	 * Turn the given URI into a fully qualified URL.
	 *
	 * @param string $uri URI to fully-qualify.
	 * @return string
	 */
	protected function prepare_url_for_request( $uri ) {
		return trailingslashit( home_url( $uri ) );
	}

	/**
	 * Follow a redirect chain until a non-redirect is received.
	 *
	 * @param Test_Response $response Test response.
	 * @return Test_Response
	 */
	protected function follow_redirects( $response ) {
		while ( $response->is_redirect() ) {
			$response = $this->get( $response->get_header( 'Location' ) );
		}

		$this->follow_redirects = false;

		return $response;
	}

	/**
	 * Visit the given URI with a GET request, expecting a JSON response.
	 *
	 * @param string $uri     URI to "get".
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function get_json( $uri, array $headers = [] ) {
		return $this->json( 'GET', $uri, [], $headers );
	}

	/**
	 * Call the given URI with a JSON request.
	 *
	 * @param string $method  Request method.
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function json( $method, $uri, array $data = [], array $headers = [] ) {
		$content = wp_json_encode( $data );

		$headers = array_merge(
			[
				'CONTENT_LENGTH' => mb_strlen( $content, '8bit' ),
				'CONTENT_TYPE'   => 'application/json',
				'Accept'         => 'application/json',
			],
			$headers
		);

		return $this->call( $method, $uri, [], $this->transform_headers_to_server_vars( $headers ), $content );
	}

	/**
	 * Visit the given URI with a POST request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function post( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'POST', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a POST request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function post_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'POST', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a PUT request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function put( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PUT', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a PUT request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function put_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'PUT', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a PATCH request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function patch( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PATCH', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a PATCH request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function patch_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'PATCH', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a DELETE request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function delete( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'DELETE', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a DELETE request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function delete_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'DELETE', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a OPTIONS request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function options( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'OPTIONS', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a OPTIONS request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function options_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'OPTIONS', $uri, $data, $headers );
	}
}
