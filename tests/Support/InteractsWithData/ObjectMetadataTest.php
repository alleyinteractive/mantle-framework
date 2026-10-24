<?php
namespace Mantle\Tests\Support\InteractsWithDataTest;

use Closure;
use Mantle\Support\Object_Metadata;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Object Meta Data test case.
 */
#[Group('interactsWithData')]
class ObjectMetadataTest extends FrameworkTestCase {
	#[DataProvider('objectTypeProvider')]
	public function test_get_data( string $object_type ): void {
		$object_id = static::factory()->{$object_type}->create();

		update_metadata( $object_type, $object_id, 'test_meta', 'test' );
		update_metadata( $object_type, $object_id, 'another_test_meta', 1234 );

		$metadata = Object_Metadata::of( $object_type, $object_id, 'test_meta' );

		$this->assertSame( 'test', $metadata->string() );
		$this->assertSame( [ 'test' ], $metadata->array() );

		$metadata = Object_Metadata::of( $object_type, $object_id, 'another_test_meta' );

		$this->assertSame( '1234', $metadata->string() );
		$this->assertSame( 1234, $metadata->int() );
		$this->assertSame( [ '1234' ], $metadata->array() );
		$this->assertSame( 1234.0, $metadata->float() );
	}

	#[DataProvider('objectMetaHelperProvider')]
	public function test_get_data_helper( string $object_type, Closure $method ): void {
		$object_id = static::factory()->{$object_type}->create();

		update_metadata( $object_type, $object_id, 'test_meta', 'test' );
		update_metadata( $object_type, $object_id, 'another_test_meta', 1234 );

		$metadata = $method( $object_id, 'test_meta' );

		$this->assertSame( 'test', $metadata->string() );
		$this->assertSame( [ 'test' ], $metadata->array() );

		$metadata = $method( $object_id, 'another_test_meta' );

		$this->assertSame( '1234', $metadata->string() );
		$this->assertSame( 1234, $metadata->int() );
		$this->assertSame( [ '1234' ], $metadata->array() );
		$this->assertSame( 1234.0, $metadata->float() );
	}

	#[DataProvider('objectTypeProvider')]
	public function test_add_data( string $object_type ): void {
		$object_id = static::factory()->{$object_type}->create();
		$metadata  = Object_Metadata::of( $object_type, $object_id, 'test_meta' );

		$metadata->set( 'new-test' );
		$metadata->add();

		$metadata->set( 'new-test-2' );
		$metadata->add();

		$all_values = $metadata->all()->array();

		// Test all meta values from the same key are retrieved.
		$this->assertCount( 2, $all_values );
		$this->assertSame( [ 'new-test', 'new-test-2' ], $metadata->all()->array() );
		$this->assertSame( get_metadata( $object_type, $object_id, 'test_meta' ),$all_values );
	}

	#[DataProvider('objectTypeProvider')]
	public function test_update_data( string $object_type ): void {
		$object_id = static::factory()->{$object_type}->create();

		$metadata = Object_Metadata::of( $object_type, $object_id, 'test_meta' );

		$metadata->set( 'new-test' );
		$metadata->save();

		$this->assertSame( 'new-test', $metadata->value() );
		$this->assertSame( 'new-test', $metadata->string() );

		$this->assertSame( 'new-test', get_metadata( $object_type, $object_id, 'test_meta', true ) );
	}

	#[DataProvider('objectTypeProvider')]
	public function test_delete_data( string $object_type ): void {
		$object_id = static::factory()->{$object_type}->create();

		update_metadata( $object_type, $object_id, 'test_meta', 'test' );

		$metadata = Object_Metadata::of( $object_type, $object_id, 'test_meta' );

		$metadata->delete();

		$this->assertEmpty( get_metadata( $object_type, $object_id, 'test_meta', true ) );
	}

	#[DataProvider('objectTypeProvider')]
	public function test_delete_data_value( string $object_type ): void {
		$object_id = static::factory()->{$object_type}->create();
		$metadata = Object_Metadata::of( $object_type, $object_id, 'test_meta' );

		$value1 = 'new-test';
		$value2 = 'new-test-2';

		$metadata->set( $value1 );
		$metadata->add();

		$metadata->set( $value2 );
		$metadata->add();

		$metadata->delete_value( $value1 );

		$refreshed = Object_Metadata::of( $object_type, $object_id, 'test_meta' );

		$this->assertSame( $value2, $refreshed->value() );
		$this->assertNotEmpty( $refreshed->string() );
	}

	#[DataProvider('objectTypeProvider')]
	public function test_default_value( string $object_type ): void {
		$object_id = static::factory()->{$object_type}->create();

		$metadata = Object_Metadata::of( $object_type, $object_id, 'test_meta', 'default' );

		$this->assertSame( 'default', $metadata->value() );
		$this->assertSame( 'default', $metadata->string() );
		$this->assertSame( [ 'default' ], $metadata->array() );
		$this->assertSame( 0, $metadata->int() );
		$this->assertSame( 0.0, $metadata->float() );
	}

	#[DataProvider('objectMetaHelperProvider')]
	public function test_default_value_helper( string $object_type, Closure $method ): void {
		$object_id = static::factory()->{$object_type}->create();

		$metadata = $method( $object_id, 'test_meta', 'default' );

		$this->assertSame( 'default', $metadata->value() );
		$this->assertSame( 'default', $metadata->string() );
		$this->assertSame( [ 'default' ], $metadata->array() );
		$this->assertSame( 0, $metadata->int() );
		$this->assertSame( 0.0, $metadata->float() );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function objectTypeProvider(): array {
		return [
			'post' => [ 'post' ],
			'term' => [ 'term' ],
			'user' => [ 'user' ],
			'comment' => [ 'comment' ],
		];
	}

	/**
	 * @return array<string, array{0: string, 1: Closure}>
	 */
	public static function objectMetaHelperProvider(): array {
		return [
			'post'    => [ 'post', \Mantle\Support\Helpers\post_meta( ... ) ],
			'term'    => [ 'term', \Mantle\Support\Helpers\term_meta( ... ) ],
			'user'    => [ 'user', \Mantle\Support\Helpers\user_meta( ... ) ],
			'comment' => [ 'comment', \Mantle\Support\Helpers\comment_meta( ... ) ],
		];
	}
}
