<?php
/**
 * Assertable_Json_String class file
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
	 * The original encoded JSON.
	 *
	 * @var string|array|Jsonable|JsonSerializable
	 */
	public $json;

	/**
	 * The decoded JSON contents.
	 *
	 * @var array|null
	 */
	protected ?array $decoded;

	/**
	 * Constructor.
	 *
	 * @param string|array|Jsonable|JsonSerializable $jsonable
	 */
	public function __construct( $jsonable ) {
		$this->json = $jsonable;

		if ( $jsonable instanceof JsonSerializable ) {
			$this->decoded = $jsonable->jsonSerialize();
		} elseif ( $jsonable instanceof Jsonable ) {
			$this->decoded = json_decode( $jsonable->to_json(), true );
		} elseif ( is_array( $jsonable ) ) {
			$this->decoded = $jsonable;
		} else {
			$this->decoded = json_decode( $jsonable, true );
		}

		if ( is_null( $this->decoded ) || false === $this->decoded ) {
			PHPUnit::fail( 'Invalid JSON was returned from the route.' );
		}
	}

	/**
	 * Validate and return the decoded response JSON.
	 *
	 * @param string|null $key Key to retrieve, optional.
	 * @return mixed
	 */
	public function json( $key = null ) {
		return data_get( $this->decoded, $key );
	}

	/**
	 * Assert that the expected value and type exists at the given path in the response.
	 *
	 * @param  string $path
	 * @param  mixed  $expect
	 * @return $this
	 */
	public function assertPath( $path, $expect ) {
		PHPUnit::assertSame( $expect, $this->json( $path ) );

		return $this;
	}

	/**
	 * Assert that a specific path exists in the response.
	 *
	 * @param string $path Path to check.
	 */
	public function assertPathExists( string $path ) {
		PHPUnit::assertNotNull( $this->json( $path ) );

		return $this;
	}

	/**
	 * Assert that a specific path does not exist in the response.
	 *
	 * @param string $path Path to check.
	 */
	public function assertPathMissing( string $path ) {
		PHPUnit::assertNull( $this->json( $path ) );

		return $this;
	}

	/**
	 * Assert that the response has the similar JSON as given.
	 *
	 * @param  array  $data
	 * @return $this
	 */
	public function assertSimilar( array $data ) {
		$actual = json_encode( Arr::sort_recursive(
			(array) $this->decoded
		) );

		PHPUnit::assertEquals( json_encode( Arr::sort_recursive( $data ) ), $actual );

		return $this;
	}

	/**
	 * Assert that the response has a given JSON structure.
	 *
	 * @param  array|null  $structure
	 * @param  array|null  $response_data
	 * @return $this
	 */
	public function assertStructure( array $structure = null, $response_data = null ) {
		if ( is_null( $structure ) ) {
			return $this->assertSimilar( $this->decoded );
		}

		if ( ! is_null( $response_data ) ) {
			return ( new static( $response_data ) )->assertStructure( $structure );
		}

		foreach ( $structure as $key => $value ) {
			if ( is_array( $value ) && '*' === $key ) {
				PHPUnit::assertIsArray($this->decoded);

				foreach ( $this->decoded as $item ) {
					$this->assertStructure( $structure['*'], $item );
				}
			} elseif ( is_array( $value ) ) {
				PHPUnit::assertArrayHasKey($key, $this->decoded);

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
	 * @return $this
	 */
	public function assertExact( array $data ) {
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
	 * @return $this
	 */
	public function assertFragment( array $data ) {
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
	 * @return $this
	 */
	public function assertMissing( array $data, $exact = false ) {
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
	 * @return $this
	 */
	public function assertMissingExact( array $data ) {
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
	 * @return $this
	 */
	public function assertCount( int $count, $key = null ) {
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
	 * Get the total number of items in the underlying JSON array.
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->decoded );
	}

	/**
	 * Determine whether an offset exists.
	 *
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->decoded[ $offset ] );
	}

	/**
	 * Get the value at the given offset.
	 *
	 * @param  string  $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ): mixed {
		return $this->decoded[ $offset ];
	}

	/**
	 * Set the value at the given offset.
	 *
	 * @param  string  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void {
		$this->decoded[ $offset ] = $value;
	}

	/**
	 * Unset the value at the given offset.
	 *
	 * @param  string  $offset
	 * @return void
	 */
	public function offsetUnset($offset): void {
		unset( $this->decoded[ $offset ] );
	}
}
