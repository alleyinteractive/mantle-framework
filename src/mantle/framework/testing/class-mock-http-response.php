<?php
/**
 * This file contains the Mock_Http_Response class
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing;

use Mantle\Framework\Contracts\Support\Arrayable;

/**
 * This class provides a mock HTTP response to be able to simulate HTTP requests
 * in WordPress.
 *
 * Example:
 *
 *     Mock_Http_Response::create()
 *         ->with_response_code( 404 )
 *         ->with_body( '{"error":true}' )
 *         ->with_header( 'Content-Type', 'application/json' );
 */
class Mock_Http_Response implements Arrayable {
	/**
	 * Response data.
	 *
	 * @var array
	 */
	public $response = [];

	/**
	 * Mock_Http_Response constructor.
	 */
	public function __construct() {
		$this->response = [
			'headers'  => [],
			'body'     => '',
			'response' => [
				'code'    => 200,
				'message' => get_status_header_desc( 200 ),
			],
			'cookies'  => [],
			'filename' => '',
		];
	}

	/**
	 * Helper method to create a response.
	 *
	 * @return Mock_Http_Response
	 */
	public static function create(): Mock_Http_Response {
		return new static();
	}

	/**
	 * Add a header to the response.
	 *
	 * @param string $key   Header key.
	 * @param string $value Header value.
	 * @return Mock_Http_Response This object.
	 */
	public function with_header( string $key, string $value ): Mock_Http_Response {
		$this->response['headers'][ $key ] = $value;

		return $this;
	}

	/**
	 * Set the response code. The response message will be inferred from that.
	 *
	 * @param int $code HTTP response code.
	 * @return Mock_Http_Response This object.
	 */
	public function with_response_code( int $code ): Mock_Http_Response {
		$this->response['response'] = [
			'code'    => $code,
			'message' => get_status_header_desc( $code ),
		];

		return $this;
	}

	/**
	 * Set the response body.
	 *
	 * @param string $body Response body.
	 * @return Mock_Http_Response This object.
	 */
	public function with_body( string $body ): Mock_Http_Response {
		$this->response['body'] = $body;

		return $this;
	}

	/**
	 * Set a response cookie.
	 *
	 * @param \WP_Http_Cookie $cookie Cookie.
	 * @return Mock_Http_Response This object.
	 */
	public function with_cookie( \WP_Http_Cookie $cookie ): Mock_Http_Response {
		$this->response['cookies'][] = $cookie;

		return $this;
	}

	/**
	 * Set the filename value for the mock response.
	 *
	 * @param string $filename Filename.
	 * @return Mock_Http_Response This object.
	 */
	public function with_filename( string $filename ): Mock_Http_Response {
		$this->response['filename'] = $filename;

		return $this;
	}

	/**
	 * Set the JSON body for the response.
	 *
	 * Also sets the proper JSON Content-Type header.
	 *
	 * @param array|string $payload JSON Payload to use.
	 */
	public function with_json( $payload ): Mock_Http_Response {
		return $this
			->with_body( ! is_string( $payload ) ? wp_json_encode( $payload ) : $payload )
			->with_header( 'Content-Type', 'application/json' );
	}

	/**
	 * Set the response as a 301 redirect
	 *
	 * @param string $url Redirect URL.
	 */
	public function with_redirect( string $url ): Mock_Http_Response {
		return $this
			->with_header( 'Location', $url )
			->with_response_code( 301 );
	}

	/**
	 * Set the response as a 302 temporary redirect
	 *
	 * @param string $url Redirect URL.
	 */
	public function with_temporary_redirect( string $url ): Mock_Http_Response {
		return $this
			->with_header( 'Location', $url )
			->with_response_code( 302 );
	}

	/**
	 * Returns the combined response array.
	 *
	 * @return array WP_Http response array, per WP_Http::request().
	 */
	public function to_array() {
		return $this->response;
	}
}
