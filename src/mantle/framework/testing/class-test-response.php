<?php // phpcs:disable WordPress.NamingConventions.ValidFunctionName
/**
 * This file contains the Test_Response class
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing;

use Exception;
use Mantle\Framework\Support\Arr;
use Mantle\Framework\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

use function Mantle\Framework\Helpers\data_get;

/**
 * Faux "Response" class for unit testing.
 */
class Test_Response {

	/**
	 * Response headers.
	 *
	 * @var array
	 */
	public $headers;

	/**
	 * Response content.
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * Response status code.
	 *
	 * @var int
	 */
	protected $status_code;

	/**
	 * Create a new test response instance.
	 *
	 * @param string|null $content HTTP response body.
	 * @param int         $status  HTTP response status code.
	 * @param array       $headers HTTP response headers.
	 */
	public function __construct( ?string $content = '', int $status = 200, array $headers = [] ) {
		$this->set_content( $content )
			->set_status_code( $status )
			->set_headers( $headers );
	}

	/**
	 * Sets the response status code.
	 *
	 * @param int $code Status code.
	 * @return $this
	 */
	public function set_status_code( int $code ): object {
		$this->status_code = $code;

		return $this;
	}

	/**
	 * Retrieves the status code for the current web response.
	 *
	 * @return int
	 */
	public function get_status_code(): int {
		return $this->status_code;
	}

	/**
	 * Sets the response content.
	 *
	 * @param string|null $content Response content.
	 * @return $this
	 */
	public function set_content( ?string $content ): object {
		$this->content = $content ?? '';

		return $this;
	}

	/**
	 * Gets the current response content.
	 *
	 * @return string|false
	 */
	public function get_content() {
		return $this->content;
	}

	/**
	 * Sets the response headers.
	 *
	 * @param array $headers Headers to set, as key => value pairs.
	 * @return $this
	 */
	public function set_headers( array $headers ): object {
		$this->headers = $headers;

		return $this;
	}

	/**
	 * Gets the current response headers.
	 *
	 * @return array
	 */
	public function get_headers() {
		return $this->headers;
	}

	/**
	 * Gets the current response headers.
	 *
	 * @param string      $key     Header to return.
	 * @param string|null $default If the header is not set, default to return.
	 * @return string|null
	 */
	public function get_header( string $key, string $default = null ): ?string {
		// If the header is set and not null, return the string value.
		if ( isset( $this->headers[ $key ] ) ) {
			// Account for multiple headers with the same key.
			return is_array( $this->headers[ $key ] )
				? (string) $this->headers[ $key ][0] ?? ''
				: (string) $this->headers[ $key ];
		}

		// If the header is set and null, return that. Otherwise, the default.
		return array_key_exists( $key, $this->headers )
			? $this->headers[ $key ]
			: $default;
	}

	/**
	 * Assert that the response has a successful status code.
	 *
	 * @return $this
	 */
	public function assertSuccessful() {
		$actual = $this->get_status_code();
		PHPUnit::assertTrue(
			$actual >= 200 && $actual < 300,
			'Response status code [' . $actual . '] is not a successful status code.'
		);

		return $this;
	}

	/**
	 * Assert that the response has a 200 status code.
	 *
	 * @return $this
	 */
	public function assertOk() {
		return $this->assertStatus( 200 );
	}

	/**
	 * Assert that the response has the given status code.
	 *
	 * @param int $status Status code to assert.
	 * @return $this
	 */
	public function assertStatus( $status ) {
		$actual = $this->get_status_code();

		PHPUnit::assertSame(
			$actual,
			$status,
			"Expected status code {$status} but received {$actual}."
		);

		return $this;
	}

	/**
	 * Assert that the response has a 201 status code.
	 *
	 * @return $this
	 */
	public function assertCreated() {
		return $this->assertStatus( 201 );
	}

	/**
	 * Assert that the response has the given status code and no content.
	 *
	 * @param int $status Status code to assert. Defaults to 204.
	 * @return $this
	 */
	public function assertNoContent( $status = 204 ) {
		$this->assertStatus( $status );

		PHPUnit::assertEmpty( $this->get_content(), 'Response content is not empty.' );

		return $this;
	}

	/**
	 * Assert that the response has a not found status code.
	 *
	 * @return $this
	 */
	public function assertNotFound() {
		return $this->assertStatus( 404 );
	}

	/**
	 * Assert that the response has a forbidden status code.
	 *
	 * @return $this
	 */
	public function assertForbidden() {
		return $this->assertStatus( 403 );
	}

	/**
	 * Assert that the response has an unauthorized status code.
	 *
	 * @return $this
	 */
	public function assertUnauthorized() {
		return $this->assertStatus( 401 );
	}

	/**
	 * Assert whether the response is redirecting to a given URI.
	 *
	 * @param string|null $uri URI to assert redirection to.
	 * @return $this
	 */
	public function assertRedirect( $uri = null ) {
		PHPUnit::assertTrue(
			$this->is_redirect(),
			'Response status code [' . $this->get_status_code() . '] is not a redirect status code.'
		);

		if ( ! is_null( $uri ) ) {
			$this->assertLocation( $uri );
		}

		return $this;
	}

	/**
	 * Is the response a redirect of some form?
	 *
	 * @param string|null $location Location to check with the redirect.
	 * @return bool
	 */
	public function is_redirect( string $location = null ): bool {
		return in_array( $this->get_status_code(), [ 201, 301, 302, 303, 307, 308 ], true )
			&& ( null === $location ?: $location === $this->get_header( 'Location' ) ); // phpcs:ignore WordPress.PHP.DisallowShortTernary.Found
	}

	/**
	 * Assert that the current location header matches the given URI.
	 *
	 * @param string $uri URI to assert that the location header is set to.
	 * @return $this
	 */
	public function assertLocation( $uri ) {
		PHPUnit::assertEquals(
			trailingslashit( home_url( $uri ) ),
			trailingslashit( home_url( $this->get_header( 'Location' ) ) )
		);

		return $this;
	}

	/**
	 * Asserts that the response contains the given header and equals the
	 * optional value.
	 *
	 * @param string $header_name Header name (key) to assert.
	 * @param mixed  $value       Header value to assert.
	 * @return $this
	 */
	public function assertHeader( $header_name, $value = null ) {
		PHPUnit::assertArrayHasKey(
			$header_name,
			$this->headers,
			"Header [{$header_name}] not present on response."
		);

		$actual = $this->get_header( $header_name );

		if ( ! is_null( $value ) ) {
			PHPUnit::assertEquals(
				$value,
				$this->get_header( $header_name ),
				"Header [{$header_name}] was found, but value [{$actual}] does not match [{$value}]."
			);
		}

		return $this;
	}

	/**
	 * Asserts that the response does not contains the given header.
	 *
	 * @param string $header_name Header name (key) to check.
	 * @return $this
	 */
	public function assertHeaderMissing( $header_name ) {
		PHPUnit::assertArrayNotHasKey(
			$header_name,
			$this->headers,
			"Unexpected header [{$header_name}] is present on response."
		);

		return $this;
	}

	/**
	 * Asset that the contents matches an expected value.
	 *
	 * @param mixed $value Contents to compare.
	 * @return $this
	 */
	public function assertContent( $value ) {
		PHPUnit::assertEquals( $value, $this->get_content() );
		return $this;
	}

	/**
	 * Assert that the given string is contained within the response.
	 *
	 * @param string $value String to search for.
	 * @return $this
	 */
	public function assertSee( $value ) {
		PHPUnit::assertStringContainsString( (string) $value, $this->get_content() );

		return $this;
	}

	/**
	 * Look for $values in $content in the specified order.
	 *
	 * @throws \Exception On failure.
	 *
	 * @param array  $values  Strings in which to look for in order.
	 * @param string $content Content in which to look.
	 * @return bool True on success.
	 */
	public function see_in_order( array $values, string $content ): bool {
		$position = 0;

		foreach ( $values as $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$value_position = mb_strpos( $content, $value, $position );

			if ( false === $value_position || $value_position < $position ) {
				throw new Exception(
					sprintf(
						'Failed asserting that \'%s\' contains "%s" in specified order.',
						$content,
						$value
					)
				);
			}

			$position = $value_position + mb_strlen( $value );
		}

		return true;
	}

	/**
	 * Assert that the given strings are contained in order within the response.
	 *
	 * @param array $values Values to check.
	 * @return $this
	 */
	public function assertSeeInOrder( array $values ) {
		try {
			PHPUnit::assertTrue( $this->see_in_order( $values, $this->get_content() ) );
		} catch ( Exception $exception ) {
			PHPUnit::fail( $exception->getMessage() );
		}

		return $this;
	}

	/**
	 * Assert that the given string is contained within the response text.
	 *
	 * @param string $value Value to check.
	 * @return $this
	 */
	public function assertSeeText( $value ) {
		PHPUnit::assertStringContainsString( (string) $value, wp_strip_all_tags( $this->get_content() ) );

		return $this;
	}

	/**
	 * Assert that the given strings are contained in order within the response
	 * text.
	 *
	 * @param array $values Values to check.
	 * @return $this
	 */
	public function assertSeeTextInOrder( array $values ) {
		try {
			PHPUnit::assertTrue(
				$this->see_in_order( $values, wp_strip_all_tags( $this->get_content() ) )
			);
		} catch ( Exception $exception ) {
			PHPUnit::fail( $exception->getMessage() );
		}

		return $this;
	}

	/**
	 * Assert that the given string is not contained within the response.
	 *
	 * @param string $value Value to check.
	 * @return $this
	 */
	public function assertDontSee( $value ) {
		PHPUnit::assertStringNotContainsString( (string) $value, $this->get_content() );

		return $this;
	}

	/**
	 * Assert that the given string is not contained within the response text.
	 *
	 * @param string $value Value to check.
	 * @return $this
	 */
	public function assertDontSeeText( $value ) {
		PHPUnit::assertStringNotContainsString( (string) $value, wp_strip_all_tags( $this->get_content() ) );

		return $this;
	}

	/**
	 * Checks each of the WP_Query is_* functions/properties against expected
	 * boolean value.
	 *
	 * @see Test_Case::assertQueryTrue()
	 *
	 * @param string ...$prop Any number of WP_Query properties that are expected
	 *                        to be true for the current request.
	 */
	public function assertQueryTrue( ...$prop ) {
		Test_Case::assertQueryTrue( ...$prop );

		return $this;
	}

	/**
	 * Assert that a given ID matches the global queried object ID.
	 *
	 * @param int $id Expected ID.
	 * @return $this
	 */
	public function assertQueriedObjectId( int $id ) {
		Test_Case::assertQueriedObjectId( $id );

		return $this;
	}

	/**
	 * Assert that a given object is equivalent to the global queried object.
	 *
	 * @param Object $object Expected object.
	 * @return $this
	 */
	public function assertQueriedObject( $object ) {
		Test_Case::assertQueriedObject( $object );

		return $this;
	}

	/**
	 * Assert that the response is a superset of the given JSON.
	 *
	 * @param  array $data
	 * @param  bool  $strict
	 * @return $this
	 */
	// public function assertJson( array $data, $strict = false ) {
	// 	Test_Case::assertArraySubset(
	// 		$data,
	// 		$this->decode_response_json(),
	// 		$strict,
	// 		$this->assertJsonMessage( $data )
	// 	);

	// 	return $this;
	// }

	/**
	 * Get the assertion message for assertJson.
	 *
	 * @param  array $data
	 * @return string
	 */
	protected function assertJsonMessage( array $data ) {
		$expected = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		$actual = wp_json_encode( $this->decode_response_json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		return 'Unable to find JSON: ' . PHP_EOL . PHP_EOL .
				"[{$expected}]" . PHP_EOL . PHP_EOL .
				'within response JSON:' . PHP_EOL . PHP_EOL .
				"[{$actual}]." . PHP_EOL . PHP_EOL;
	}

	/**
	 * Assert that the expected value and type exists at the given path in the response.
	 *
	 * @param  string $path
	 * @param  mixed  $expect
	 * @return $this
	 */
	public function assertJsonPath( $path, $expect ) {
		PHPUnit::assertSame( $expect, $this->json( $path ) );

		return $this;
	}

	/**
	 * Assert that the response has the exact given JSON.
	 *
	 * @param  array $data
	 * @return $this
	 */
	public function assertExactJson( array $data ) {
			$actual = wp_json_encode(
				Arr::sort_recursive(
					(array) $this->decode_response_json()
				)
			);

			PHPUnit::assertEquals( wp_json_encode( Arr::sort_recursive( $data ) ), $actual );

			return $this;
	}

		/**
		 * Assert that the response contains the given JSON fragment.
		 *
		 * @param  array $data
		 * @return $this
		 */
	public function assertJsonFragment( array $data ) {
			$actual = wp_json_encode(
				Arr::sort_recursive(
					(array) $this->decode_response_json()
				)
			);

		foreach ( Arr::sort_recursive( $data ) as $key => $value ) {
				$expected = $this->json_search_strings( $key, $value );

				PHPUnit::assertTrue(
					Str::contains( $actual, $expected ),
					'Unable to find JSON fragment: ' . PHP_EOL . PHP_EOL .
						'[' . wp_json_encode( [ $key => $value ] ) . ']' . PHP_EOL . PHP_EOL .
						'within' . PHP_EOL . PHP_EOL .
						"[{$actual}]."
				);
		}

			return $this;
	}

		/**
		 * Assert that the response does not contain the given JSON fragment.
		 *
		 * @param  array $data
		 * @param  bool  $exact
		 * @return $this
		 */
	public function assertJsonMissing( array $data, $exact = false ) {
		if ( $exact ) {
				return $this->assertJsonMissingExact( $data );
		}

			$actual = wp_json_encode(
				Arr::sort_recursive(
					(array) $this->decode_response_json()
				)
			);

		foreach ( Arr::sort_recursive( $data ) as $key => $value ) {
				$unexpected = $this->json_search_strings( $key, $value );

				PHPUnit::assertFalse(
					Str::contains( $actual, $unexpected ),
					'Found unexpected JSON fragment: ' . PHP_EOL . PHP_EOL .
						'[' . wp_json_encode( [ $key => $value ] ) . ']' . PHP_EOL . PHP_EOL .
						'within' . PHP_EOL . PHP_EOL .
						"[{$actual}]."
				);
		}

			return $this;
	}

		/**
		 * Assert that the response does not contain the exact JSON fragment.
		 *
		 * @param  array $data
		 * @return $this
		 */
	public function assertJsonMissingExact( array $data ) {
			$actual = wp_json_encode(
				Arr::sort_recursive(
					(array) $this->decode_response_json()
				)
			);

		foreach ( Arr::sort_recursive( $data ) as $key => $value ) {
			$unexpected = $this->json_search_strings( $key, $value );

			if ( ! Str::contains( $actual, $unexpected ) ) {
				return $this;
			}
		}

		PHPUnit::fail(
			'Found unexpected JSON fragment: ' . PHP_EOL . PHP_EOL .
			'[' . wp_json_encode( $data ) . ']' . PHP_EOL . PHP_EOL .
			'within' . PHP_EOL . PHP_EOL .
			"[{$actual}]."
		);
	}

	/**
	 * Get the strings we need to search for when examining the JSON.
	 *
	 * @param  string $key
	 * @param  string $value
	 * @return array
	 */
	protected function json_search_strings( $key, $value ) {
		$needle = substr( wp_json_encode( [ $key => $value ] ), 1, -1 );

		return [
			$needle . ']',
			$needle . '}',
			$needle . ',',
		];
	}

	/**
	 * Assert that the response JSON has the expected count of items at the given key.
	 *
	 * @param  int         $count
	 * @param  string|null $key
	 * @return $this
	 */
	public function assertJsonCount( int $count, $key = null ) {
		if ( ! is_null( $key ) ) {
			PHPUnit::assertCount(
				$count,
				data_get( $this->json(), $key ),
				"Failed to assert that the response count matched the expected {$count}"
			);

			return $this;
		}

		PHPUnit::assertCount(
			$count,
			$this->json(),
			"Failed to assert that the response count matched the expected {$count}"
		);

		return $this;
	}

	/**
	 * Validate and return the decoded response JSON.
	 *
	 * @param  string|null $key Key to retrieve (passed to {@see data_get()}).
	 * @return mixed
	 */
	public function decode_response_json( $key = null ) {
		$decoded_response = json_decode( $this->get_content(), true );

		if ( is_null( $decoded_response ) || false === $decoded_response ) {
			PHPUnit::fail( 'Invalid JSON was returned from the route.' );
		}

		return data_get( $decoded_response, $key );
	}

	/**
	 * Validate and return the decoded response JSON.
	 *
	 * @param string|null $key Key to retrieve, optional.
	 * @return mixed
	 */
	public function json( $key = null ) {
		return $this->decode_response_json( $key );
	}
}
