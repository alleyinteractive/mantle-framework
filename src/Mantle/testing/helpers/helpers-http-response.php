<?php
/**
 * HTTP Response Testing Helpers
 *
 * @package Mantle
 */

namespace Mantle\Testing;

/**
 * Create a new Mock HTTP Response
 *
 * @param string $body    Response body.
 * @param array  $headers Response headers.
 */
function mock_http_response( string $body = '', array $headers = [] ): Mock_Http_Response {
	return new Mock_Http_Response( $body, $headers );
}

/**
 * Create a new Mock HTTP Response Sequence
 */
function mock_http_sequence(): Mock_Http_Sequence {
	return new Mock_Http_Sequence();
}
