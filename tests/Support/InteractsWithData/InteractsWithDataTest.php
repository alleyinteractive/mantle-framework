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
	/**
	 * Return a value untouched so PHPStan cannot narrow its type.
	 */
	private function opaque( mixed $value ): mixed {
		return $value;
	}

	public function test_as_string(): void {
		$this->assertSame( 'test', TestableInteractsWithData::create( 'test' )->string() );
		$this->assertSame( '1234', TestableInteractsWithData::create( 1234 )->string() );
		$this->assertSame( '1.1', TestableInteractsWithData::create( 1.1 )->string() );
		$this->assertSame( '1', TestableInteractsWithData::create( true )->string() );
		$this->assertSame( '', TestableInteractsWithData::create( false )->string() );
		$this->assertSame( [ 'test' ], TestableInteractsWithData::create( 'test' )->array() );
	}

	public function test_as_stringable(): void {
		$option = TestableInteractsWithData::create( 'test' );

		$stringable = $this->opaque( $option->stringable() );
		$this->assertInstanceOf( \Mantle\Support\Stringable::class, $stringable );
		$this->assertSame(
			'test',
			$option->stringable()->value(),
		);
	}

	public function test_as_int(): void {
		$this->assertSame( 1, TestableInteractsWithData::create( 1 )->int() );
		$this->assertSame( 1, TestableInteractsWithData::create( '1' )->int() );
		$this->assertSame( 1, TestableInteractsWithData::create( 1.1 )->int() );
		$this->assertSame( 1, TestableInteractsWithData::create( true )->int() );
		$this->assertSame( 0, TestableInteractsWithData::create( false )->int() );
		$this->assertSame( [ 1 ], TestableInteractsWithData::create( 1 )->array() );
	}

	public function test_as_float(): void {
		$this->assertSame( 1.1, TestableInteractsWithData::create( 1.1 )->float() );
		$this->assertSame( 1.1, TestableInteractsWithData::create( '1.1' )->float() );
		$this->assertSame( 1.0, TestableInteractsWithData::create( 1 )->float() );
		$this->assertSame( 1.0, TestableInteractsWithData::create( true )->float() );
		$this->assertSame( 0.0, TestableInteractsWithData::create( false )->float() );
	}

	public function test_as_bool(): void {
		$this->assertTrue( TestableInteractsWithData::create( true )->bool() );
		$this->assertTrue( TestableInteractsWithData::create( '1' )->bool() );
		$this->assertTrue( TestableInteractsWithData::create( 1 )->bool() );
		$this->assertTrue( TestableInteractsWithData::create( 'true' )->bool() );
		$this->assertFalse( TestableInteractsWithData::create( false )->bool() );
		$this->assertFalse( TestableInteractsWithData::create( 'false' )->bool() );
		$this->assertTrue( TestableInteractsWithData::create( 'random string' )->bool() );
		$this->assertFalse( TestableInteractsWithData::create( '0' )->bool() );
		$this->assertFalse( TestableInteractsWithData::create( null )->bool() );
	}

	public function test_truthy(): void {
		$this->assertTrue( TestableInteractsWithData::create( true )->truthy() );
		$this->assertTrue( TestableInteractsWithData::create( '1' )->truthy() );
		$this->assertTrue( TestableInteractsWithData::create( 1 )->truthy() );
		$this->assertTrue( TestableInteractsWithData::create( 'true' )->truthy() );
		$this->assertTrue( TestableInteractsWithData::create( 'anything' )->truthy() );
		$this->assertTrue( TestableInteractsWithData::create( 'false' )->truthy() );
		$this->assertTrue( TestableInteractsWithData::create( 'random string' )->truthy() );
		$this->assertFalse( TestableInteractsWithData::create( false )->truthy() );
		$this->assertFalse( TestableInteractsWithData::create( '0' )->truthy() );
	}

	public function test_is_false(): void {
		$this->assertTrue( TestableInteractsWithData::create( false )->is_false() );
		$this->assertFalse( TestableInteractsWithData::create( true )->is_false() );
		$this->assertFalse( TestableInteractsWithData::create( 0 )->is_false() );
		$this->assertFalse( TestableInteractsWithData::create( '0' )->is_false() );
		$this->assertFalse( TestableInteractsWithData::create( '' )->is_false() );
		$this->assertFalse( TestableInteractsWithData::create( null )->is_false() );
		$this->assertFalse( TestableInteractsWithData::create( 'false' )->is_false() );
		$this->assertFalse( TestableInteractsWithData::create( [] )->is_false() );
	}

	public function test_is_true(): void {
		$this->assertTrue( TestableInteractsWithData::create( true )->is_true() );
		$this->assertFalse( TestableInteractsWithData::create( false )->is_true() );
		$this->assertFalse( TestableInteractsWithData::create( 1 )->is_true() );
		$this->assertFalse( TestableInteractsWithData::create( '1' )->is_true() );
		$this->assertFalse( TestableInteractsWithData::create( 'true' )->is_true() );
		$this->assertFalse( TestableInteractsWithData::create( null )->is_true() );
		$this->assertFalse( TestableInteractsWithData::create( 0 )->is_true() );
		$this->assertFalse( TestableInteractsWithData::create( [] )->is_true() );
	}

	public function test_as_array(): void {
		$this->assertSame( [ 'test' ], TestableInteractsWithData::create( [ 'test' ] )->array() );
		$this->assertSame( [ 'test' ], TestableInteractsWithData::create( 'test' )->array() );
	}

	public function test_as_collection(): void {
		$this->assertSame( [ 'test' ], TestableInteractsWithData::create( [ 'test' ] )->collection()->toArray() );
		$this->assertSame( [ 'test' ], TestableInteractsWithData::create( 'test' )->collection()->toArray() );
	}

	public function test_as_date(): void {
		$this->assertNull( TestableInteractsWithData::create( '' )->date() );

		$this->assertSame( '2020-01-01', TestableInteractsWithData::create( '2020-01-01' )->date()?->format( 'Y-m-d' ) );
	}

	public function test_as_object(): void {
		$this->assertEquals( (object) [ 'test' ], TestableInteractsWithData::create( (object) [ 'test' ] )->object() );
		$this->assertEquals( (object) [ 'test' ], TestableInteractsWithData::create( [ 'test' ] )->object() );
	}

	public function test_array_property(): void {
		$this->assertSame( 'test', TestableInteractsWithData::create( [ 'key' => 'test' ] )->get( 'key' )->string() );
		$this->assertSame( 'value', TestableInteractsWithData::create( [ 'sub' => [ 'value' ] ] )->get( 'sub.0' )->string() );
	}

	public function test_object_property(): void {
		$this->assertSame( 'test', TestableInteractsWithData::create( (object) [ 'key' => 'test' ] )->get( 'key' )->string() );
		$this->assertSame( 'value', TestableInteractsWithData::create( (object) [ 'sub' => [ 'value' ] ] )->get( 'sub.0' )->string() );
	}

	public function test_json_serializable(): void {
		$this->assertSame( json_encode( 'test' ), json_encode( TestableInteractsWithData::create( 'test' ) ) );
		$this->assertSame( json_encode( [ 'test' ] ), json_encode( TestableInteractsWithData::create( [ 'test' ] ) ) );
	}

	public function test_arrayaccess_get(): void {
		$this->assertSame( 'test', TestableInteractsWithData::create( [ 'test' ] )[0] );
		$this->assertSame( 'test', TestableInteractsWithData::create( [ 'key' => 'test' ] )['key'] );
	}

	public function test_arrayaccess_set(): void {
		$option = TestableInteractsWithData::create( [ 'test', 'test2' ] );

		$this->assertSame( [ 'test', 'test2' ], $option->array() );

		$option[0] = 'new-test';

		$this->assertSame( [ 'new-test', 'test2' ], $option->array() );
	}

	public function test_arrayaccess_unset(): void {
		$option = TestableInteractsWithData::create( [ 'test', 'test2' ] );

		$this->assertSame( [ 'test', 'test2' ], $option->array() );

		unset( $option[0] );

		$this->assertSame( [ 1 => 'test2' ], $option->array() );
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

	public function test_mixed_helper(): void {
		$this->assertSame( 'test', \Mantle\Support\Helpers\mixed( 'test' )->string() );
		$this->assertSame( [ 'test' ], \Mantle\Support\Helpers\mixed( 'test' )->array() );
		$this->assertSame( '1234', \Mantle\Support\Helpers\mixed( 1234 )->string() );
		$this->assertSame( '1234', \Mantle\Support\Helpers\mixed( '1234' )->string() );
		$this->assertSame( '1.1', \Mantle\Support\Helpers\mixed( 1.1 )->string() );
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
