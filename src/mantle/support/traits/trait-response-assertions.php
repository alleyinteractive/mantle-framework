<?php
/**
 * Response_Assertions trait file
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Trait for response assertions that can be used across different response classes.
 *
 * This trait provides assertion methods that can be used by both:
 * - Mantle\Testing\Test_Response (via method aliasing to existing methods)
 * - Mantle\Http_Client\Response (via new assertion methods)
 *
 * Classes using this trait must implement the abstract methods to provide
 * access to response data.
 */
trait Response_Assertions {

	/**
	 * Get the response status code.
	 */
	abstract protected function getResponseStatusCode(): int;

	/**
	 * Get a response header.
	 *
	 * @param string $header Header name.
	 * @param mixed  $default Default value if header not found.
	 * @return mixed
	 */
	abstract protected function getResponseHeader( string $header, mixed $default = null ): mixed;

	/**
	 * Get the response content/body.
	 */
	abstract protected function getResponseContent(): ?string;

	/**
	 * Get all response headers.
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function getResponseHeaders(): array;

	/**
	 * Assert that the response has a successful status code.
	 */
	public function assertSuccessful(): static {
		$actual = $this->getResponseStatusCode();

		PHPUnit::assertTrue(
			$actual >= 200 && $actual < 300,
			'Response status code [' . $actual . '] is not a successful status code.'
		);

		return $this;
	}

	/**
	 * Assert that the response has a 200 status code.
	 */
	public function assertOk(): static {
		return $this->assertStatus( 200 );
	}

	/**
	 * Assert that the response has the given status code.
	 *
	 * @param int $status Status code to assert.
	 */
	public function assertStatus( $status ): static {
		$actual = $this->getResponseStatusCode();

		PHPUnit::assertSame(
			$actual,
			$status,
			"Expected status code {$status} but received {$actual}."
		);

		return $this;
	}

	/**
	 * Assert that the response has a 201 status code.
	 */
	public function assertCreated(): static {
		return $this->assertStatus( 201 );
	}

	/**
	 * Assert that the response has the given status code and no content.
	 *
	 * @param int $status Status code to assert. Defaults to 204.
	 */
	public function assertNoContent( $status = 204 ): static {
		$this->assertStatus( $status );

		PHPUnit::assertEmpty( $this->getResponseContent(), 'Response content is not empty.' );

		return $this;
	}

	/**
	 * Assert that the response has a not found status code.
	 */
	public function assertNotFound(): static {
		return $this->assertStatus( 404 );
	}

	/**
	 * Assert that the response has a forbidden status code.
	 */
	public function assertForbidden(): static {
		return $this->assertStatus( 403 );
	}

	/**
	 * Assert that the response has an unauthorized status code.
	 */
	public function assertUnauthorized(): static {
		return $this->assertStatus( 401 );
	}

	/**
	 * Assert that the response has a client error status code.
	 */
	public function assertClientError(): static {
		$status = $this->getResponseStatusCode();

		PHPUnit::assertTrue(
			$status >= 400 && $status < 500,
			"Response status code [{$status}] is not a client error status code.",
		);

		return $this;
	}

	/**
	 * Assert that the response has a server error status code.
	 */
	public function assertServerError(): static {
		$status = $this->getResponseStatusCode();

		PHPUnit::assertTrue(
			$status >= 500,
			"Response status code [{$status}] is not a server error status code.",
		);

		return $this;
	}

	/**
	 * Assert that the response has a redirect status code.
	 *
	 * @param string|null $uri Optional URI to assert the redirect location.
	 */
	public function assertRedirect( ?string $uri = null ): static {
		$status = $this->getResponseStatusCode();

		PHPUnit::assertTrue(
			$status >= 300 && $status < 400,
			"Response status code [{$status}] is not a redirect status code.",
		);

		if ( ! is_null( $uri ) ) {
			$this->assertLocation( $uri );
		}

		return $this;
	}

	/**
	 * Assert that the response has the given location header value.
	 *
	 * @param string $uri Expected location URI.
	 */
	public function assertLocation( $uri ): static {
		PHPUnit::assertEquals(
			$uri,
			$this->getResponseHeader( 'Location' ),
			'Expected location header does not match.'
		);

		return $this;
	}

	/**
	 * Assert that the response has the given header.
	 *
	 * @param string $header_name Header name to check.
	 * @param mixed  $value       Optional. Expected header value.
	 */
	public function assertHeader( $header_name, $value = null ): static {
		$header_value = $this->getResponseHeader( $header_name );

		PHPUnit::assertNotNull(
			$header_value,
			"Header [{$header_name}] not present on response."
		);

		if ( ! is_null( $value ) ) {
			PHPUnit::assertEquals(
				$value,
				$header_value,
				"Header [{$header_name}] does not match expected value."
			);
		}

		return $this;
	}

	/**
	 * Assert that the response does not have the given header.
	 *
	 * @param string $header_name Header name to check.
	 * @param mixed  $value       Optional. Value that should not be present.
	 */
	public function assertHeaderMissing( string $header_name, mixed $value = null ): static {
		$header_value = $this->getResponseHeader( $header_name );

		if ( is_null( $value ) ) {
			PHPUnit::assertNull(
				$header_value,
				"Header [{$header_name}] is present on response."
			);
		} else {
			PHPUnit::assertNotEquals(
				$value,
				$header_value,
				"Header [{$header_name}] contains the unexpected value [{$value}]."
			);
		}

		return $this;
	}

	/**
	 * Assert that the response content matches the given value.
	 *
	 * @param mixed $value Expected content.
	 */
	public function assertContent( mixed $value ): static {
		PHPUnit::assertEquals(
			$value,
			$this->getResponseContent(),
			'Response content does not match expected value.'
		);

		return $this;
	}

	/**
	 * Assert that the response content does not match the given value.
	 *
	 * @param mixed $value Value that should not match.
	 */
	public function assertNotContent( mixed $value ): static {
		PHPUnit::assertNotEquals(
			$value,
			$this->getResponseContent(),
			'Response content matches unexpected value.'
		);

		return $this;
	}

	/**
	 * Assert that the response contains the given string.
	 *
	 * @param string   $needle String to search for.
	 * @param int|null $count  Optional. Expected number of occurrences.
	 */
	public function assertSee( string $needle, ?int $count = null ): static {
		$content = $this->getResponseContent();

		if ( is_null( $count ) ) {
			PHPUnit::assertStringContainsString(
				$needle,
				$content,
				"The string [{$needle}] was not found in the response."
			);
		} else {
			$actual_count = substr_count( $content, $needle );
			PHPUnit::assertEquals(
				$count,
				$actual_count,
				"Expected to see [{$needle}] {$count} times, but saw it {$actual_count} times."
			);
		}

		return $this;
	}

	/**
	 * Alias for assertSee() method.
	 *
	 * @param string   $needle String to search for.
	 * @param int|null $count  Optional. Expected number of occurrences.
	 */
	public function assertContains( string $needle, ?int $count = null ): static {
		return $this->assertSee( $needle, $count );
	}

	/**
	 * Assert that the response does not contain the given string.
	 *
	 * @param mixed $value String to search for.
	 */
	public function assertDontSee( $value ): static {
		PHPUnit::assertStringNotContainsString(
			$value,
			$this->getResponseContent(),
			"The string [{$value}] was found in the response."
		);

		return $this;
	}

	/**
	 * Assert that the response is a JSON response.
	 */
	public function assertIsJson(): static {
		$content_type = $this->getResponseHeader( 'Content-Type' );

		if ( empty( $content_type ) ) {
			PHPUnit::fail( 'Response is not JSON.' );
		}

		// Check that the content-type header contains 'application/json'.
		PHPUnit::assertStringContainsString( 'application/json', $content_type );

		// Try to decode the content and see if it's valid JSON.
		$content = $this->getResponseContent();
		if ( ! empty( $content ) ) {
			$decoded = json_decode( $content, true );
			PHPUnit::assertNotNull( $decoded, 'Response content is not valid JSON.' );
		}

		return $this;
	}

	/**
	 * Assert that the response is not a JSON response.
	 */
	public function assertIsNotJson(): static {
		$content_type = $this->getResponseHeader( 'Content-Type' );

		PHPUnit::assertStringNotContainsString( 'application/json', $content_type );

		// If there's content, make sure it's not valid JSON.
		$content = $this->getResponseContent();
		if ( ! empty( $content ) ) {
			$decoded = json_decode( $content, true );
			PHPUnit::assertNull( $decoded, 'Response content is valid JSON.' );
		}

		return $this;
	}

	/**
	 * Assert that the response is an HTML response.
	 */
	public function assertIsHtml(): static {
		PHPUnit::assertStringContainsString( 'text/html', $this->getResponseHeader( 'Content-Type' ) );

		return $this;
	}

	/**
	 * Assert that the response is not an HTML response.
	 */
	public function assertIsNotHtml(): static {
		PHPUnit::assertStringNotContainsString( 'text/html', $this->getResponseHeader( 'Content-Type' ) );

		return $this;
	}
}