<?php
/**
 * Assertable_Json_String class file
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use ArrayAccess;
use Countable;
use JsonSerializable;
use Mantle\Contracts\Support\Jsonable;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

use function Mantle\Support\Helpers\data_get;

/**
 * Assertions that can be made against a JSON string.
 */
class Assertable_Json_String implements ArrayAccess, Countable {
	/**
	 * The decoded JSON contents.
	 */
	protected ?array $decoded = null;

	/**
	 * Constructor.
	 *
	 * @param string|array|Jsonable|JsonSerializable $json The original encoded JSON.
	 */
	public function __construct( public $json ) {
		if ( $this->json instanceof JsonSerializable ) {
			$this->decoded = $this->json->jsonSerialize();
		} elseif ( $this->json instanceof Jsonable ) {
			$this->decoded = json_decode( $this->json->to_json(), true );
		} elseif ( is_array( $this->json ) ) {
			$this->decoded = $this->json;
		} else {
			$decoded = json_decode( $this->json, true );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				PHPUnit::fail( 'Invalid JSON was returned from the response: ' . json_last_error_msg() );
			}

			$this->decoded = is_array( $decoded ) ? $decoded : null;
		}

		if ( null === $this->decoded ) {
			PHPUnit::fail( 'Invalid JSON was returned from the response.' );
		}
	}

	/**
	 * Retrieve the decoded JSON.
	 */
	public function get_decoded(): array {
		return $this->decoded;
	}

	/**
	 * Validate and return the decoded response JSON.
	 *
	 * @param string|null $key Key to retrieve, optional.
	 */
	public function json( string|array|int|null $key = null ): mixed {
		return data_get( $this->decoded, $key );
	}

	/**
	 * Assert that the expected value and type exists at the given path in the response.
	 *
	 * @param  string $path
	 * @param  mixed  $expect
	 * @param  string $message Optional message on failure.
	 */
	public function assertPath( string $path, mixed $expect, string $message = '' ): static {
		PHPUnit::assertSame( $expect, $this->json( $path ), $message );

		return $this;
	}

	/**
	 * Assert that the value at the given path in the response matches the given regular expression.
	 *
	 * @param string $path  Path to check.
	 * @param string $regex Regular expression to match against.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathMatches( string $path, string $regex, string $message = '' ): static {
		PHPUnit::assertMatchesRegularExpression( $regex, (string) $this->json( $path ), $message );

		return $this;
	}

	/**
	 * Assert that a specific path does not match the given regular expression pattern in the response.
	 *
	 * @param string $path Path to check.
	 * @param string $pattern Regular expression pattern to match.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathNotMatches( string $path, string $pattern, string $message = '' ): static {
		PHPUnit::assertDoesNotMatchRegularExpression( $pattern, (string) $this->json( $path ), $message );

		return $this;
	}

	/**
	 * Assert that a specific path exists in the response.
	 *
	 * @param string $path Path to check.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathExists( string $path, string $message = '' ): static {
		PHPUnit::assertNotNull( $this->json( $path ), $message );

		return $this;
	}

	/**
	 * Assert that a specific path does not exist in the response.
	 *
	 * @param string $path Path to check.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathMissing( string $path, string $message = '' ): static {
		PHPUnit::assertNull( $this->json( $path ), $message );

		return $this;
	}

	/**
	 * Assert that a specific path is empty in the response.
	 *
	 * @param string $path  Path to check.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathEmpty( string $path, string $message = '' ): static {
		PHPUnit::assertEmpty( $this->json( $path ), $message );

		return $this;
	}

	/**
	 * Assert that a specific path is not empty in the response.
	 *
	 * @param string $path  Path to check.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathNotEmpty( string $path, string $message = '' ): static {
		PHPUnit::assertNotEmpty( $this->json( $path ), $message );

		return $this;
	}

	/**
	 * Assert that a specific path contains the given string value in the response.
	 *
	 * @param string $path  Path to check.
	 * @param string $needle Value to check for.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathContains( string $path, string $needle, string $message = '' ): static {
		PHPUnit::assertStringContainsString(
			$needle,
			(string) $this->json( $path ),
			$message,
		);

		return $this;
	}

	/**
	 * Assert that a specific path does not contain the given string value in the response.
	 *
	 * @param string $path  Path to check.
	 * @param string $needle Value to check for.
	 * @param string $message Optional message on failure.
	 */
	public function assertPathNotContains( string $path, string $needle, string $message = '' ): static {
		PHPUnit::assertStringNotContainsString(
			$needle,
			(string) $this->json( $path ),
			$message,
		);

		return $this;
	}

	/**
	 * Assert that the value at the given path in the response passes the given truth test.
	 *
	 * @param string   $path     Path to check.
	 * @param callable $callback Callback that receives the value and returns true if it passes.
	 * @param string   $message  Optional message on failure.
	 *
	 * @phpstan-param (callable(mixed): bool) $callback
	 */
	public function assertPathCallback( string $path, callable $callback, string $message = '' ): static {
		$value = $this->json( $path );

		if ( empty( $message ) ) {
			$message = "The value at path [{$path}] did not pass the given truth test.";
		}

		PHPUnit::assertTrue( (bool) $callback( $value ), $message );

		return $this;
	}

	/**
	 * Assert that the response has the similar JSON as given.
	 *
	 * @param  array $data
	 */
	public function assertSimilar( array $data ): static {
		$actual = json_encode( Arr::sort_recursive(
			(array) $this->decoded
		) );

		PHPUnit::assertEquals( json_encode( Arr::sort_recursive( $data ) ), $actual );

		return $this;
	}

	/**
	 * Assert that the response has a given JSON structure.
	 *
	 * @param  array|null $structure
	 * @param  array|null $response_data
	 * @return static
	 */
	public function assertStructure( ?array $structure = null, $response_data = null ) {
		if ( is_null( $structure ) ) {
			return $this->assertSimilar( $this->decoded );
		}

		if ( ! is_null( $response_data ) ) {
			return ( new static( $response_data ) )->assertStructure( $structure );
		}

		foreach ( $structure as $key => $value ) {
			if ( is_array( $value ) && '*' === $key ) {
				PHPUnit::assertIsArray( $this->decoded );

				foreach ( $this->decoded as $item ) {
					$this->assertStructure( $structure['*'], $item );
				}
			} elseif ( is_array( $value ) ) {
				PHPUnit::assertArrayHasKey( $key, $this->decoded );

				$this->assertStructure( $structure[ $key ], $this->decoded[ $key ] );
			} else {
				PHPUnit::assertArrayHasKey( $value, $this->decoded );
			}
		}

		return $this;
	}

	/**
	 * Assert that the response has the exact given JSON.
	 *
	 * @param  array $data
	 */
	public function assertExact( array $data ): static {
		$actual = wp_json_encode(
			Arr::sort_recursive(
				(array) $this->json()
			)
		);

		PHPUnit::assertEquals( wp_json_encode( Arr::sort_recursive( $data ) ), $actual );

		return $this;
	}

	/**
	 * Assert that the response contains the given JSON fragment.
	 *
	 * @param  array $data Data to compare.
	 */
	public function assertFragment( array $data ): static {
		$actual = wp_json_encode(
			Arr::sort_recursive(
				(array) $this->json()
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
	 * @param  array $data Data to compare.
	 * @param  bool  $exact Flag for exact match, defaults to false.
	 */
	public function assertMissing( array $data, $exact = false ): static {
		if ( $exact ) {
			return $this->assertMissingExact( $data );
		}

		$actual = wp_json_encode(
			Arr::sort_recursive(
				(array) $this->json()
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
	 */
	public function assertMissingExact( array $data ): static {
		$actual = wp_json_encode(
			Arr::sort_recursive(
				(array) $this->json()
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
	 * Assert that the response JSON has the expected count of items at the given key.
	 *
	 * @param  int         $count
	 * @param  string|null $key
	 */
	public function assertCount( int $count, $key = null ): static {
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
	 * Get the strings we need to search for when examining the JSON.
	 *
	 * @param  string $key
	 * @param  string $value
	 */
	protected function json_search_strings( $key, $value ): array {
		$needle = substr( (string) wp_json_encode( [ $key => $value ] ), 1, -1 );

		return [
			$needle . ']',
			$needle . '}',
			$needle . ',',
		];
	}

	/**
	 * Get the total number of items in the underlying JSON array.
	 */
	public function count(): int {
		return count( $this->decoded );
	}

	/**
	 * Determine whether an offset exists.
	 *
	 * @param  mixed $offset
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->decoded[ $offset ] );
	}

	/**
	 * Get the value at the given offset.
	 *
	 * @param  string $offset
	 */
	public function offsetGet( $offset ): mixed {
		return $this->decoded[ $offset ];
	}

	/**
	 * Set the value at the given offset.
	 *
	 * @param  string $offset
	 * @param  mixed  $value
	 */
	public function offsetSet( $offset, $value ): void {
		$this->decoded[ $offset ] = $value;
	}

	/**
	 * Unset the value at the given offset.
	 *
	 * @param  string $offset
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->decoded[ $offset ] );
	}
}
