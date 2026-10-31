<?php
/**
 * Class file for Nullable_Term_Objects_Test_Case
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Helpers;

use Mantle\Testing\FrameworkTestCase;
use function Mantle\Support\Helpers\get_term_object;
use function Mantle\Support\Helpers\get_term_object_by;

/**
 * Unit tests for nullable term object functions.
 */
class NullableTermObjectsTest extends FrameworkTestCase {
	/**
	 * An ID that can never belong to a real object.
	 */
	private const IMPOSSIBLE_ID = PHP_INT_MAX;

	/**
	 * Test that `get_term_object()` returns a term object.
	 */
	public function test_term_object_returns_term(): void {
		$known_id = static::factory()->term->create();

		$this->assertIsInt( $known_id );

		$found_object = get_term_object( $known_id );

		$this->assertInstanceOf( \WP_Term::class, $found_object );

		$this->assertSame( $known_id, $found_object->term_id );
	}

	/**
	 * Test that `get_term_object()` returns a term object given a taxonomy.
	 */
	public function test_term_object_with_taxonomy_returns_term(): void {
		$known_term = static::factory()->term->create_and_get();

		$found_object = get_term_object( $known_term->term_id, $known_term->taxonomy );

		$this->assertInstanceOf( \WP_Term::class, $found_object );

		$this->assertSame( $known_term->term_id, $found_object->term_id );
	}

	/**
	 * Test that `get_term_object()` honors the requested filter.
	 */
	public function test_term_object_returns_filtered_term(): void {
		$slug = 'test-term';

		$known_id = static::factory()->term->create(
			[
				'slug' => $slug,
			]
		);

		$this->assertIsInt( $known_id );

		$found_object = get_term_object( $known_id, '', \OBJECT, 'edit' );

		$this->assertInstanceOf( \WP_Term::class, $found_object );
		$this->assertSame( $slug, $found_object->slug );

		\add_filter( 'edit_term_slug', '__return_empty_string' );

		$found_object = get_term_object( $known_id, '', \OBJECT, 'edit' );

		$this->assertInstanceOf( \WP_Term::class, $found_object );
		$this->assertEmpty( $found_object->slug );
	}

	/**
	 * Test that `get_term_object()` returns null when there is no term.
	 */
	public function test_term_object_returns_null(): void {
		$found_object = get_term_object( self::IMPOSSIBLE_ID );

		$this->assertNull( $found_object );
	}

	/**
	 * Test that `get_term_object()` returns null when `get_term()` returns errors.
	 */
	public function test_term_object_returns_null_not_error(): void {
		$invalid = 0;

		$this->assertWPError( \get_term( $invalid ) );

		$this->assertNull( get_term_object( $invalid ) );
	}

	/**
	 * Test that `get_term_object_by()` returns a term object.
	 */
	public function test_term_object_by_returns_term(): void {
		$known_term = static::factory()->term->create_and_get();

		$found_object = get_term_object_by( 'slug', $known_term->slug, $known_term->taxonomy );

		$this->assertInstanceOf( \WP_Term::class, $found_object );

		$this->assertSame( $known_term->term_id, $found_object->term_id );
	}

	/**
	 * Test that `get_term_object_by()` honors the requested filter.
	 */
	public function test_term_object_by_returns_filtered_term(): void {
		$known_term = static::factory()->term->create_and_get(
			[
				// 'slug' => $this->data1,
			]
		);

		$found_object = get_term_object_by(
			'slug',
			$known_term->slug,
			$known_term->taxonomy,
			\OBJECT,
			'edit'
		);

		$this->assertInstanceOf( \WP_Term::class, $found_object );
		$this->assertSame(
			$known_term->slug,
			$found_object->slug
		);

		\add_filter( 'edit_term_slug', '__return_empty_string' );

		$found_object = get_term_object_by(
			'slug',
			$known_term->slug,
			$known_term->taxonomy,
			\OBJECT,
			'edit'
		);

		$this->assertInstanceOf( \WP_Term::class, $found_object );
		$this->assertEmpty(
			$found_object->slug
		);
	}

	/**
	 * Test that `get_term_object_by()` returns null when there is no term.
	 */
	public function test_term_object_by_returns_null(): void {
		$valid = static::factory()->term->create_and_get();

		$found_object = get_term_object_by( 'slug', '', $valid->taxonomy );

		$this->assertNull( $found_object );
	}
}
