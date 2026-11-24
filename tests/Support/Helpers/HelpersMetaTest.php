<?php
declare(strict_types=1);

namespace Mantle\Tests\Support\Helpers;

use Mantle\Testing\FrameworkTestCase;

use function Mantle\Support\Helpers\register_meta_from_file;
use function Mantle\Support\Helpers\register_meta_helper;

class HelpersMetaTest extends FrameworkTestCase {
	/**
	 * Tests the functionality of register_meta_helper.
	 */
	public function test_register_meta_helper(): void {
		$this->expectApplied( 'mantle_register_meta_helper_args' )->times( 3 )->andReturnArray();

		// Register post meta to test.
		register_meta_helper(
			'post',
			[ 'post' ],
			'test_post_meta_key'
		);

		// Register term meta to test.
		register_meta_helper(
			'term',
			[ 'category' ],
			'test_term_meta_key'
		);

		// Ensure meta is registered for the post type specified.
		$registered = get_registered_meta_keys( 'post', 'post' );

		// Ensure defaults were applied properly.
		$this->assertTrue( $registered['test_post_meta_key']['show_in_rest'] );
		$this->assertTrue( $registered['test_post_meta_key']['single'] );
		$this->assertSame( 'string', $registered['test_post_meta_key']['type'] );

		// Ensure meta is not registered for a different post type.
		$registered = get_registered_meta_keys( 'post', 'page' );
		$this->assertArrayNotHasKey( 'test_post_meta_key', $registered );

		// Ensure meta is registered for the term type specified.
		$registered = get_registered_meta_keys( 'term', 'category' );

		// Ensure defaults were applied properly.
		$this->assertTrue( $registered['test_term_meta_key']['show_in_rest'] );
		$this->assertTrue( $registered['test_term_meta_key']['single'] );
		$this->assertSame( 'string', $registered['test_term_meta_key']['type'] );

		// Ensure meta is not registered for a different term type.
		$registered = get_registered_meta_keys( 'term', 'post_tag' );
		$this->assertArrayNotHasKey( 'test_term_meta_key', $registered );

		// Ensure custom options are supported.
		register_meta_helper(
			'post',
			[ 'post' ],
			'test_custom_meta_key',
			[
				'sanitize_callback' => 'absint',
				'show_in_rest'      => false,
				'single'            => false,
				'type'              => 'integer',
			]
		);
		$registered = get_registered_meta_keys( 'post', 'post' );
		$this->assertSame( 'absint', $registered['test_custom_meta_key']['sanitize_callback'] );
		$this->assertFalse( $registered['test_custom_meta_key']['show_in_rest'] );
		$this->assertFalse( $registered['test_custom_meta_key']['single'] );
		$this->assertSame( 'integer', $registered['test_custom_meta_key']['type'] );
	}

	public function test_register_meta_from_file(): void {
		$registered = get_registered_meta_keys( 'post', 'post' );

		$this->assertArrayNotHasKey( 'testable_meta_key', $registered );

		register_meta_from_file( __DIR__ . '/fixtures/meta.json', 'post' );

		$registered = get_registered_meta_keys( 'post', 'post' );
		$this->assertArrayHasKey( 'testable_meta_key', $registered );
		$this->assertSame( 'string', $registered['testable_meta_key']['type'] );
		$this->assertTrue( $registered['testable_meta_key']['single'] );
		$this->assertTrue( $registered['testable_meta_key']['show_in_rest'] );
	}

	public function test_register_meta_from_file_with_schema(): void {
		$registered = get_registered_meta_keys( 'post', 'post' );

		$this->assertArrayNotHasKey( 'testable_schema_meta_key', $registered );

		register_meta_from_file( __DIR__ . '/fixtures/meta-with-schema.json', 'post' );

		$registered = get_registered_meta_keys( 'post', 'post' );
		$this->assertArrayHasKey( 'testable_schema_meta_key', $registered );
		$this->assertSame( 'string', $registered['testable_schema_meta_key']['type'] );
		$this->assertTrue( $registered['testable_schema_meta_key']['single'] );
		$this->assertTrue( $registered['testable_schema_meta_key']['show_in_rest'] );
	}

	public function test_register_meta_from_file_invalid_file(): void {
		$this->expectException( \InvalidArgumentException::class );
		register_meta_from_file( __DIR__ . '/fixtures/non-existent.json', 'post' );
	}
}
