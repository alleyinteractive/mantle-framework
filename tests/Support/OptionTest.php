<?php
namespace Mantle\Tests\Support;

use Mantle\Support\Option;
use Mantle\Testing\Framework_Test_Case;

/**
 * Option test case.
 */
class OptionTest extends Framework_Test_Case {
	public function test_as_string(): void {
		update_option( 'test_option', 'test' );

		$this->assertEquals( 'test', Option::of( 'test_option' )->string() );
	}

	public function test_as_stringable(): void {
		update_option( 'test_option', 'test' );

		$option = Option::of( 'test_option' );

		$this->assertInstanceOf( \Mantle\Support\Stringable::class, $option->stringable() );
		$this->assertEquals(
			'test',
			$option->stringable()->value(),
		);
	}

	public function test_as_int(): void {
		update_option( 'test_option', 1 );

		$this->assertEquals( 1, Option::of( 'test_option' )->int() );
		$this->assertEquals( '1', Option::of( 'test_option' )->string() );
	}

	public function test_as_float(): void {
		update_option( 'test_option', 1.1 );

		$this->assertEquals( 1.1, Option::of( 'test_option' )->float() );
		$this->assertEquals( '1.1', Option::of( 'test_option' )->string() );
	}

	public function test_as_bool(): void {
		update_option( 'test_option', true );

		$this->assertEquals( true, Option::of( 'test_option' )->bool() );
		$this->assertEquals( '1', Option::of( 'test_option' )->string() );

		update_option( 'test_option', false );

		$this->assertEquals( false, Option::of( 'test_option' )->bool() );
		$this->assertEquals( '', Option::of( 'test_option' )->string() );
	}

	public function test_as_array(): void {
		update_option( 'test_option', [ 'test' ] );

		$this->assertEquals( [ 'test' ], Option::of( 'test_option' )->array() );
	}

	public function test_as_collection(): void {
		update_option( 'test_option', [ 'test' ] );

		$this->assertEquals( [ 'test' ], Option::of( 'test_option' )->collection()->toArray() );
	}

	public function test_as_date(): void {
		$this->assertNull( Option::of( 'unknown' )->date() );

		update_option( 'test_option', '2020-01-01' );

		$this->assertEquals( '2020-01-01', Option::of( 'test_option' )->date()->format( 'Y-m-d' ) );
	}

	public function test_as_object(): void {
		update_option( 'test_option', (object) [ 'test' ] );

		$this->assertEquals( (object) [ 'test' ], Option::of( 'test_option' )->object() );
	}

	public function test_array_property(): void {
		update_option( 'test_option', [
			'sub' => 'test',
			'sub2' => [
				'sub3' => 'test-sub-sub',
			],
		] );

		$option = Option::of( 'test_option' );

		$this->assertEquals( 'test', $option->get( 'sub' )->string() );
		$this->assertEquals( [ 'sub3' => 'test-sub-sub' ], $option->get( 'sub2' )->array() );
		$this->assertEquals( 'test-sub-sub', $option->get( 'sub2.sub3' )->string() );
	}

	public function test_object_property(): void {
		update_option( 'test_option', (object) [
			'sub' => 'test',
			'sub2' => (object) [
				'sub3' => 'test-sub-sub',
			],
		] );

		$option = Option::of( 'test_option' );

		$this->assertEquals( 'test', $option->get( 'sub' )->string() );
		$this->assertEquals( (object) [ 'sub3' => 'test-sub-sub' ], $option->get( 'sub2' )->object() );
		$this->assertEquals( 'test-sub-sub', $option->get( 'sub2.sub3' )->string() );
	}

	public function test_json_serializable(): void {
		update_option( 'test_option', [ 'test' ] );

		$this->assertEquals( json_encode( [ 'test' ] ), Option::of( 'test_option' )->to_json() );
	}

	public function test_update(): void {
		update_option( 'test_option', 'test' );

		$option = Option::of( 'test_option' );

		$this->assertEquals( 'test', $option->string() );
		$option->set( 'new-test' );

		$this->assertEquals( 'new-test', $option->string() );
		$this->assertEquals( 'new-test', get_option( 'test_option' ) );
	}

	public function test_arrayaccess_get(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertEquals( 'test', $option[0] );
		$this->assertEquals( 'test2', $option[1] );
	}

	public function test_arrayaccess_set(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertEquals( [ 'test', 'test2' ], $option->array() );

		$option[0] = 'new-test';

		$this->assertEquals( [ 'new-test', 'test2' ], $option->array() );
		$this->assertEquals( [ 'new-test', 'test2' ], get_option( 'test_option' ) );
	}

	public function test_arrayaccess_unset(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertEquals( [ 'test', 'test2' ], $option->array() );

		unset( $option[0] );

		$this->assertEquals( [ 1 => 'test2' ], $option->array() );
		$this->assertEquals( [ 1 => 'test2' ], get_option( 'test_option' ) );
	}

	public function test_arrayaccess_isset(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertTrue( isset( $option[0] ) );
		$this->assertFalse( isset( $option[2] ) );
	}

	public function test_array_has(): void {
		update_option( 'test_option', [
			'key' => 'test',
			'key2' => 'test2',
		] );

		$option = Option::of( 'test_option' );

		$this->assertTrue( $option->has( 'key' ) );
		$this->assertTrue( $option->has( 'key', 'key2' ) );
		$this->assertFalse( $option->has( 'key3' ) );
		$this->assertFalse( $option->has( 'key2', 'key3' ) );
	}

	public function test_array_has_any(): void {
		update_option( 'test_option', [
			'key' => 'test',
			'key2' => 'test2',
		] );

		$option = Option::of( 'test_option' );

		$this->assertTrue( $option->has_any( 'key' ) );
		$this->assertTrue( $option->has_any( 'key', 'key3' ) );
		$this->assertFalse( $option->has_any( 'key3', 'key4' ) );
		$this->assertFalse( $option->has_any( 'key3', 'key4', 'key5' ) );
	}

	public function test_throw_on_array_to_string(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Option value of test_option is not scalar and cannot be cast to a string.' );

		update_option( 'test_option', [ 'test' ] );
		Option::of( 'test_option' )->throw()->string();
	}

	public function test_throw_on_string_to_int(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Option value of test_option is not numeric and cannot be cast to an integer.' );

		update_option( 'test_option', 'string here 1234' );

		Option::of( 'test_option' )->throw()->int();
	}
}
