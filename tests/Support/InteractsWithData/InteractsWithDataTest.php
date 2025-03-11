<?php
namespace Mantle\Tests\Support\InteractsWithDataTest;

use Mantle\Contracts\Support\Jsonable;
use Mantle\Support\Interacts_With_Data;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Interacts with Data test case.
 */
#[Group('interactsWithData')]
class InteractsWithDataTest extends TestCase {
	public function test_as_string(): void {
		$this->assertEquals( 'test', TestableInteractsWithData::create( 'test' )->string() );
		$this->assertEquals( '1234', TestableInteractsWithData::create( 1234 )->string() );
		$this->assertEquals( '1.1', TestableInteractsWithData::create( 1.1 )->string() );
		$this->assertEquals( '1', TestableInteractsWithData::create( true )->string() );
		$this->assertEquals( '', TestableInteractsWithData::create( false )->string() );
		$this->assertEquals( [ 'test' ], TestableInteractsWithData::create( 'test' )->array() );
	}

	public function test_as_stringable(): void {
		$option = TestableInteractsWithData::create( 'test' );

		$this->assertInstanceOf( \Mantle\Support\Stringable::class, $option->stringable() );
		$this->assertEquals(
			'test',
			$option->stringable()->value(),
		);
	}

	public function test_as_int(): void {
		$this->assertEquals( 1, TestableInteractsWithData::create( 1 )->int() );
		$this->assertEquals( 1, TestableInteractsWithData::create( '1' )->int() );
		$this->assertEquals( 1, TestableInteractsWithData::create( 1.1 )->int() );
		$this->assertEquals( 1, TestableInteractsWithData::create( true )->int() );
		$this->assertEquals( 0, TestableInteractsWithData::create( false )->int() );
		$this->assertEquals( [ 1 ], TestableInteractsWithData::create( 1 )->array() );
	}

	public function test_as_float(): void {
		$this->assertEquals( 1.1, TestableInteractsWithData::create( 1.1 )->float() );
		$this->assertEquals( 1.1, TestableInteractsWithData::create( '1.1' )->float() );
		$this->assertEquals( 1.0, TestableInteractsWithData::create( 1 )->float() );
		$this->assertEquals( 1.0, TestableInteractsWithData::create( true )->float() );
		$this->assertEquals( 0.0, TestableInteractsWithData::create( false )->float() );
	}

	public function test_as_bool(): void {
		$this->assertEquals( true, TestableInteractsWithData::create( true )->bool() );
		$this->assertEquals( true, TestableInteractsWithData::create( '1' )->bool() );
		$this->assertEquals( true, TestableInteractsWithData::create( 1 )->bool() );
		$this->assertEquals( true, TestableInteractsWithData::create( 'true' )->bool() );
		$this->assertEquals( false, TestableInteractsWithData::create( false )->bool() );
		$this->assertEquals( false, TestableInteractsWithData::create( '0' )->bool() );
		$this->assertEquals( false, TestableInteractsWithData::create( null )->bool() );
	}

	public function test_as_array(): void {
		$this->assertEquals( [ 'test' ], TestableInteractsWithData::create( [ 'test' ] )->array() );
		$this->assertEquals( [ 'test' ], TestableInteractsWithData::create( 'test' )->array() );
	}

	public function test_as_collection(): void {
		$this->assertEquals( [ 'test' ], TestableInteractsWithData::create( [ 'test' ] )->collection()->toArray() );
		$this->assertEquals( [ 'test' ], TestableInteractsWithData::create( 'test' )->collection()->toArray() );
	}

	public function test_as_date(): void {
		$this->assertNull( TestableInteractsWithData::create( '' )->date() );

		$this->assertEquals( '2020-01-01', TestableInteractsWithData::create( '2020-01-01' )->date()->format( 'Y-m-d' ) );
	}

	public function test_as_object(): void {
		$this->assertEquals( (object) [ 'test' ], TestableInteractsWithData::create( (object) [ 'test' ] )->object() );
		$this->assertEquals( (object) [ 'test' ], TestableInteractsWithData::create( [ 'test' ] )->object() );
	}

	public function test_array_property(): void {
		$this->assertEquals( 'test', TestableInteractsWithData::create( [ 'key' => 'test' ] )->get( 'key' )->string() );
		$this->assertEquals( 'value', TestableInteractsWithData::create( [ 'sub' => [ 'value' ] ] )->get( 'sub.0' )->string() );
	}

	public function test_object_property(): void {
		$this->assertEquals( 'test', TestableInteractsWithData::create( (object) [ 'key' => 'test' ] )->get( 'key' )->string() );
		$this->assertEquals( 'value', TestableInteractsWithData::create( (object) [ 'sub' => [ 'value' ] ] )->get( 'sub.0' )->string() );
	}

	public function test_json_serializable(): void {
		$this->assertEquals( json_encode( 'test' ), json_encode( TestableInteractsWithData::create( 'test' ) ) );
		$this->assertEquals( json_encode( [ 'test' ] ), json_encode( TestableInteractsWithData::create( [ 'test' ] ) ) );
	}

	public function test_arrayaccess_get(): void {
		$this->assertEquals( 'test', TestableInteractsWithData::create( [ 'test' ] )[0] );
		$this->assertEquals( 'test', TestableInteractsWithData::create( [ 'key' => 'test' ] )['key'] );
	}

	public function test_arrayaccess_set(): void {
		$option = TestableInteractsWithData::create( [ 'test', 'test2' ] );

		$this->assertEquals( [ 'test', 'test2' ], $option->array() );

		$option[0] = 'new-test';

		$this->assertEquals( [ 'new-test', 'test2' ], $option->array() );
	}

	public function test_arrayaccess_unset(): void {
		$option = TestableInteractsWithData::create( [ 'test', 'test2' ] );

		$this->assertEquals( [ 'test', 'test2' ], $option->array() );

		unset( $option[0] );

		$this->assertEquals( [ 1 => 'test2' ], $option->array() );
	}

	public function test_arrayaccess_isset(): void {
		$this->assertTrue( isset( TestableInteractsWithData::create( [ 'test' ] )[0] ) );
		$this->assertFalse( isset( TestableInteractsWithData::create( [ 'test' ] )[1] ) );
	}

	public function test_array_has(): void {
		$this->assertTrue( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has( 'key' ) );
		$this->assertFalse( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has( 'key', 'key2' ) );
		$this->assertFalse( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has( 'key2' ) );
		$this->assertFalse( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has( 'key2', 'key3' ) );
	}

	public function test_array_has_any(): void {
		$this->assertTrue( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has_any( 'key' ) );
		$this->assertTrue( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has_any( 'key', 'key2' ) );
		$this->assertFalse( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has_any( 'key2' ) );
		$this->assertFalse( TestableInteractsWithData::create( [ 'key' => 'test' ] )->has_any( 'key2', 'key3' ) );
	}

	public function test_throw_on_array_to_string(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Value is not scalar and cannot be cast to a string.' );

		TestableInteractsWithData::create( [ 'test' ] )->throw()->string();
	}

	public function test_throw_on_string_to_int(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Value is not numeric and cannot be cast to an integer.' );

		TestableInteractsWithData::create( 'test' )->throw()->int();
	}
}

class TestableInteractsWithData implements \ArrayAccess, Jsonable, \JsonSerializable, \Stringable {
	use Interacts_With_Data;

	public static function create( mixed $value ): static {
		return new static( $value );
	}

	public function __construct( mixed $value ) {
		$this->value = $value;
	}
}
