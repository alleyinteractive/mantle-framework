<?php
/**
 * Class file for Nullable_Term_Objects_Test_Case
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Helpers;

use WP_UnitTestCase;
use function Mantle\Framework\Helpers\get_term_object;
use function Mantle\Framework\Helpers\get_term_object_by;

/**
 * Unit tests for nullable term object functions.
 */
class Nullable_Term_Objects_Test_Case extends WP_UnitTestCase {
	/**
	 * Test that `get_term_object()` returns a term object.
	 */
	public function test_term_object_returns_term() {
		$known_id = self::factory()->term->create();

		$found_object = get_term_object( $known_id );

		$this->assertInstanceOf( \WP_Term::class, $found_object );

		$this->assertSame( $known_id, $found_object->term_id );
	}

	/**
	 * Test that `get_term_object()` returns a term object given a taxonomy.
	 */
	public function test_term_object_with_taxonomy_returns_term() {
		$known_term = self::factory()->term->create_and_get();

		$found_object = get_term_object( $known_term->term_id, $known_term->taxonomy );

		$this->assertInstanceOf( \WP_Term::class, $found_object );

		$this->assertSame( $known_term->term_id, $found_object->term_id );
	}

	/**
	 * Test that `get_term_object()` honors the requested filter.
	 */
	public function test_term_object_returns_filtered_term() {
		$slug = 'test-term';

		$known_id = self::factory()->term->create(
			[
				'slug' => $slug,
			]
		);

		$this->assertSame( $slug, get_term_object( $known_id, '', \OBJECT, 'edit' )->slug );

		\add_filter( 'edit_term_slug', '__return_empty_string' );

		$this->assertEmpty( get_term_object( $known_id, '', \OBJECT, 'edit' )->slug );
	}

	/**
	 * Test that `get_term_object()` returns null when there is no term.
	 */
	public function test_term_object_returns_null() {
		$found_object = get_term_object( $this->impossible_id );

		$this->assertNull( $found_object );
	}

	/**
	 * Test that `get_term_object()` returns null when `get_term()` returns errors.
	 */
	public function test_term_object_returns_null_not_error() {
		$invalid = 0;

		$this->assertWPError( \get_term( $invalid ) );

		$this->assertNull( get_term_object( $invalid ) );
	}

	/**
	 * Test that `get_term_object_by()` returns a term object.
	 */
	public function test_term_object_by_returns_term() {
		$known_term = self::factory()->term->create_and_get();

		$found_object = get_term_object_by( 'slug', $known_term->slug, $known_term->taxonomy );

		$this->assertInstanceOf( \WP_Term::class, $found_object );

		$this->assertSame( $known_term->term_id, $found_object->term_id );
	}

	/**
	 * Test that `get_term_object_by()` honors the requested filter.
	 */
	public function test_term_object_by_returns_filtered_term() {
		$known_term = self::factory()->term->create_and_get(
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

		$this->assertEmpty(
			$found_object->slug
		);
	}

	/**
	 * Test that `get_term_object_by()` returns null when there is no term.
	 */
	public function test_term_object_by_returns_null() {
		$valid = self::factory()->term->create_and_get();

		$found_object = get_term_object_by( 'slug', '', $valid->taxonomy );

		$this->assertNull( $found_object );
	}
}
