<?php
/**
 * This file contains the Mock_Http_Response class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Contracts\Support\Arrayable;

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
	 * Http Sequences
	 * Support for faking a series of fake responses in a specific order.
	 *
	 * @return Mock_Http_Sequence
	 */
	public static function sequence(): Mock_Http_Sequence {
		return new Mock_Http_Sequence();
	}

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
	 * Add an array of headers to the response.
	 *
	 * @param array<string, string> $headers Headers to append.
	 * @return Mock_Http_Response
	 */
	public function with_headers( array $headers ): Mock_Http_Response {
		foreach ( $headers as $key => $value ) {
			$this->with_header( $key, $value );
		}

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
	 * Alias for with_response_code().
	 *
	 * @param int $code HTTP response code.
	 * @return Mock_Http_Response This object.
	 */
	public function with_status( int $code ): Mock_Http_Response {
		return $this->with_response_code( $code );
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
	 * @return Mock_Http_Response This object.
	 */
	public function with_json( $payload ): Mock_Http_Response {
		return $this
			->with_body( ! is_string( $payload ) ? wp_json_encode( $payload ) : $payload )
			->with_header( 'Content-Type', 'application/json' );
	}

	/**
	 * Set the XML body for the response.
	 *
	 * Also sets the proper application/xml Content-Type header.
	 *
	 * @param string $payload JSON Payload to use.
	 * @return Mock_Http_Response This object.
	 */
	public function with_xml( string $payload ): Mock_Http_Response {
		return $this
			->with_body( $payload )
			->with_header( 'Content-Type', 'application/xml' );
	}

	/**
	 * Set the response as a 301 redirect
	 *
	 * @param string $url Redirect URL.
	 * @param int    $code Status code.
	 * @return Mock_Http_Response This object.
	 */
	public function with_redirect( string $url, int $code = 301 ): Mock_Http_Response {
		return $this
			->with_header( 'Location', $url )
			->with_response_code( $code );
	}

	/**
	 * Set the response as a 302 temporary redirect
	 *
	 * @param string $url Redirect URL.
	 * @return Mock_Http_Response This object.
	 */
	public function with_temporary_redirect( string $url ): Mock_Http_Response {
		return $this
			->with_header( 'Location', $url )
			->with_response_code( 302 );
	}

	/**
	 * Create a response with the file contents as the body.
	 *
	 * @throws \InvalidArgumentException If the file is not readable.
	 *
	 * @param string $file File path.
	 * @return Mock_Http_Response
	 */
	public function with_file( string $file ): Mock_Http_Response {
		if ( ! is_readable( $file ) ) {
			throw new \InvalidArgumentException( "File '{$file}' is not readable." );
		}

		// Determine the mime type.
		$mime_type = wp_check_filetype( $file );

		// Set the headers.
		if ( ! empty( $mime_type['type'] ) ) {
			$this->with_header( 'Content-Type', $mime_type['type'] );
		}

		if ( ! empty( $mime_type['ext'] ) ) {
			$this->with_header( 'Content-Disposition', "attachment; filename={$file}.{$mime_type['ext']}" );
		}

		return $this->with_body( file_get_contents( $file ) ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	}

	/**
	 * Returns the combined response array.
	 *
	 * @return array WP_Http response array, per WP_Http::request().
	 */
	public function to_array() {
		return $this->response;
	}

	/**
	 * Returns a Http_Client response object.
	 *
	 * @return \Mantle\Http_Client\Response
	 */
	public function to_response(): \Mantle\Http_Client\Response {
		return \Mantle\Http_Client\Response::create( $this->response );
	}
}
