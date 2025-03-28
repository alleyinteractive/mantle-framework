<?php
namespace Mantle\Tests\Support\InteractsWithDataTest;

use Mantle\Support\Option;
use Mantle\Testing\Framework_Test_Case;
use PHPUnit\Framework\Attributes\Group;

/**
 * Option test case.
 */
#[Group('interactsWithData')]
class OptionTest extends Framework_Test_Case {
	public function test_as_string(): void {
		update_option( 'test_option', 'test' );

		$this->assertEquals( 'test', Option::of( 'test_option' )->string() );
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
		$option->save();

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

		$option->save();

		$this->assertEquals( [ 'new-test', 'test2' ], $option->array() );
		$this->assertEquals( [ 'new-test', 'test2' ], get_option( 'test_option' ) );
	}

	public function test_arrayaccess_unset(): void {
		update_option( 'test_option', [ 'test', 'test2' ] );

		$option = Option::of( 'test_option' );

		$this->assertEquals( [ 'test', 'test2' ], $option->array() );

		unset( $option[0] );

		$option->save();

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
}
