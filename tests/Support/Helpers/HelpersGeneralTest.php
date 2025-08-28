<?php

namespace Mantle\Tests\Support\Helpers;

use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use function Mantle\Support\Helpers\class_basename;
use function Mantle\Support\Helpers\class_uses_recursive;
use function Mantle\Support\Helpers\head;
use function Mantle\Support\Helpers\last;
use function Mantle\Support\Helpers\object_get;
use function Mantle\Support\Helpers\preg_replace_array;
use function Mantle\Support\Helpers\retry;
use function Mantle\Support\Helpers\tap;
use function Mantle\Support\Helpers\throw_if;
use function Mantle\Support\Helpers\throw_unless;
use function Mantle\Support\Helpers\transform;
use function Mantle\Support\Helpers\useMemo;
use function Mantle\Support\Helpers\with;

class HelpersGeneralTest extends TestCase {
	public function testClassBasename() {
		$this->assertSame( 'Baz', class_basename( 'Foo\Bar\Baz' ) );
		$this->assertSame( 'Baz', class_basename( 'Baz' ) );
	}

	public function testObjectGet() {
		$class              = new stdClass;
		$class->name        = new stdClass;
		$class->name->first = 'Taylor';

		$this->assertSame( 'Taylor', object_get( $class, 'name.first' ) );
	}

	public function testHead() {
		$array = [ 'a', 'b', 'c' ];
		$this->assertSame( 'a', head( $array ) );
	}

	public function testLast() {
		$array = [ 'a', 'b', 'c' ];
		$this->assertSame( 'c', last( $array ) );
	}

	public function testClassUsesRecursiveShouldReturnTraitsOnParentClasses() {
		$this->assertSame( [
			SupportTestTraitTwo::class => SupportTestTraitTwo::class,
			SupportTestTraitOne::class => SupportTestTraitOne::class,
		],
			class_uses_recursive( SupportTestClassTwo::class ) );
	}

	public function testClassUsesRecursiveAcceptsObject() {
		$this->assertSame( [
			SupportTestTraitTwo::class => SupportTestTraitTwo::class,
			SupportTestTraitOne::class => SupportTestTraitOne::class,
		],
			class_uses_recursive( new SupportTestClassTwo ) );
	}

	public function testClassUsesRecursiveReturnParentTraitsFirst() {
		$this->assertSame( [
			SupportTestTraitTwo::class   => SupportTestTraitTwo::class,
			SupportTestTraitOne::class   => SupportTestTraitOne::class,
			SupportTestTraitThree::class => SupportTestTraitThree::class,
		],
			class_uses_recursive( SupportTestClassThree::class ) );
	}

	public function testTap() {
		$object = (object) [ 'id' => 1 ];
		$this->assertEquals( 2, tap( $object, function ( $object ) {
			$object->id = 2;
		} )->id );

		$mock = m::mock();
		$mock->shouldReceive( 'foo' )->once()->andReturn( 'bar' );
		$this->assertEquals( $mock, tap( $mock )->foo() );
	}

	public function testThrow() {
		$this->expectException( RuntimeException::class );

		throw_if( true, new RuntimeException );
	}

	public function testThrowReturnIfNotThrown() {
		$this->assertSame( 'foo', throw_unless( 'foo', new RuntimeException ) );
	}

	public function testThrowWithString() {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Test Message' );

		throw_if( true, RuntimeException::class, 'Test Message' );
	}

	public function testRetry() {
		$startTime = microtime( true );

		$attempts = retry( 2, function ( $attempts ) {
			if ( $attempts > 1 ) {
				return $attempts;
			}

			throw new RuntimeException;
		}, 100 );

		// Make sure we made two attempts
		$this->assertEquals( 2, $attempts );

		// Make sure we waited 100ms for the first attempt
		$this->assertTrue( microtime( true ) - $startTime >= 0.1 );
	}

	public function testRetryWithPassingWhenCallback() {
		$startTime = microtime( true );

		$attempts = retry( 2, function ( $attempts ) {
			if ( $attempts > 1 ) {
				return $attempts;
			}

			throw new RuntimeException;
		}, 100, function ( $ex ) {
			return true;
		} );

		// Make sure we made two attempts
		$this->assertEquals( 2, $attempts );

		// Make sure we waited 100ms for the first attempt
		$this->assertTrue( microtime( true ) - $startTime >= 0.1 );
	}

	public function testRetryWithFailingWhenCallback() {
		$this->expectException( RuntimeException::class );

		retry( 2, function ( $attempts ) {
			if ( $attempts > 1 ) {
				return $attempts;
			}

			throw new RuntimeException;
		}, 100, function ( $ex ) {
			return false;
		} );
	}

	public function testTransform() {
		$this->assertEquals( 10, transform( 5, function ( $value ) {
			return $value * 2;
		} ) );

		$this->assertNull( transform( null, function () {
			return 10;
		} ) );
	}

	public function testTransformDefaultWhenBlank() {
		$this->assertSame( 'baz', transform( null, function () {
			return 'bar';
		}, 'baz' ) );

		$this->assertSame( 'baz', transform( '', function () {
			return 'bar';
		}, function () {
			return 'baz';
		} ) );
	}

	public function testWith() {
		$this->assertEquals( 10, with( 10 ) );

		$this->assertEquals( 10, with( 5, function ( $five ) {
			return $five + 5;
		} ) );
	}

	public static function providesPregReplaceArrayData() {
		$pointerArray = [ 'Taylor', 'Otwell' ];

		next( $pointerArray );

		return [
			[
				'/:[a-z_]+/',
				[ '8:30', '9:00' ],
				'The event will take place between :start and :end',
				'The event will take place between 8:30 and 9:00',
			],
			[ '/%s/', [ 'Taylor' ], 'Hi, %s', 'Hi, Taylor' ],
			[ '/%s/', [ 'Taylor', 'Otwell' ], 'Hi, %s %s', 'Hi, Taylor Otwell' ],
			[ '/%s/', [], 'Hi, %s %s', 'Hi,  ' ],
			[ '/%s/', [ 'a', 'b', 'c' ], 'Hi', 'Hi' ],
			[ '//', [], '', '' ],
			[ '/%s/', [ 'a' ], '', '' ],
			// The internal pointer of this array is not at the beginning
			[ '/%s/', $pointerArray, 'Hi, %s %s', 'Hi, Taylor Otwell' ],
		];
	}

	/** @dataProvider providesPregReplaceArrayData */
	#[DataProvider( 'providesPregReplaceArrayData' )]
	public function testPregReplaceArray( $pattern, $replacements, $subject, $expectedOutput ) {
		$this->assertSame(
			$expectedOutput,
			preg_replace_array( $pattern, $replacements, $subject )
		);
	}

	public function testUseMemo() {
		// Test basic memoization
		$callCount = 0;
		$callback = function() use ( &$callCount ) {
			$callCount++;
			return 'result' . $callCount;
		};

		// First call should execute the callback
		$result1 = useMemo( $callback, [ 'dep1' ] );
		$this->assertSame( 'result1', $result1 );
		$this->assertSame( 1, $callCount );

		// Second call with same dependencies should return cached result
		$result2 = useMemo( $callback, [ 'dep1' ] );
		$this->assertSame( 'result1', $result2 );
		$this->assertSame( 1, $callCount ); // Should not increment
	}

	public function testUseMemoWithChangedDependencies() {
		$callCount = 0;
		$callback = function( $value ) use ( &$callCount ) {
			$callCount++;
			return 'result' . $callCount . '-' . $value;
		};

		// First call
		$result1 = useMemo( fn() => $callback( 'a' ), [ 'a' ] );
		$this->assertSame( 'result1-a', $result1 );
		$this->assertSame( 1, $callCount );

		// Call with different dependencies should re-execute
		$result2 = useMemo( fn() => $callback( 'b' ), [ 'b' ] );
		$this->assertSame( 'result2-b', $result2 );
		$this->assertSame( 2, $callCount );

		// Call with first dependencies again should return original cached result
		$result3 = useMemo( fn() => $callback( 'a' ), [ 'a' ] );
		$this->assertSame( 'result1-a', $result3 );
		$this->assertSame( 2, $callCount ); // Should not increment
	}

	public function testUseMemoWithComplexDependencies() {
		$callCount = 0;
		$callback = function( $data ) use ( &$callCount ) {
			$callCount++;
			return array_sum( $data ) * $callCount;
		};

		$deps1 = [ [ 1, 2, 3 ], 'string', true ];
		$deps2 = [ [ 1, 2, 3 ], 'string', true ];
		$deps3 = [ [ 1, 2, 3 ], 'string', false ]; // Different

		// First call
		$result1 = useMemo( fn() => $callback( [ 1, 2, 3 ] ), $deps1 );
		$this->assertSame( 6, $result1 );
		$this->assertSame( 1, $callCount );

		// Second call with identical complex dependencies
		$result2 = useMemo( fn() => $callback( [ 1, 2, 3 ] ), $deps2 );
		$this->assertSame( 6, $result2 );
		$this->assertSame( 1, $callCount ); // Should not increment

		// Third call with different dependencies
		$result3 = useMemo( fn() => $callback( [ 1, 2, 3 ] ), $deps3 );
		$this->assertSame( 12, $result3 ); // 6 * 2
		$this->assertSame( 2, $callCount );
	}

	public function testUseMemoWithCustomKey() {
		$callCount = 0;
		$callback = function() use ( &$callCount ) {
			$callCount++;
			return 'result' . $callCount;
		};

		// Two different useMemo calls with same dependencies but different keys
		$result1 = useMemo( $callback, [ 'dep1' ], 'key1' );
		$this->assertSame( 'result1', $result1 );
		$this->assertSame( 1, $callCount );

		$result2 = useMemo( $callback, [ 'dep1' ], 'key2' );
		$this->assertSame( 'result2', $result2 );
		$this->assertSame( 2, $callCount ); // Should execute because different key

		// Calling with same key should return cached result
		$result3 = useMemo( $callback, [ 'dep1' ], 'key1' );
		$this->assertSame( 'result1', $result3 );
		$this->assertSame( 2, $callCount ); // Should not increment
	}

	public function testUseMemoWithEmptyDependencies() {
		$callCount = 0;
		$callback = function() use ( &$callCount ) {
			$callCount++;
			return 'result' . $callCount;
		};

		// First call with empty dependencies
		$result1 = useMemo( $callback, [] );
		$this->assertSame( 'result1', $result1 );
		$this->assertSame( 1, $callCount );

		// Second call with empty dependencies should return cached result
		$result2 = useMemo( $callback, [] );
		$this->assertSame( 'result1', $result2 );
		$this->assertSame( 1, $callCount ); // Should not increment
	}

	public function testUseMemoDefaultDependencies() {
		$callCount = 0;
		$callback = function() use ( &$callCount ) {
			$callCount++;
			return 'result' . $callCount;
		};

		// First call without dependencies (default empty array)
		$result1 = useMemo( $callback );
		$this->assertSame( 'result1', $result1 );
		$this->assertSame( 1, $callCount );

		// Second call should return cached result
		$result2 = useMemo( $callback );
		$this->assertSame( 'result1', $result2 );
		$this->assertSame( 1, $callCount ); // Should not increment
	}
}

trait SupportTestTraitOne
{
	//
}

trait SupportTestTraitTwo
{
	use SupportTestTraitOne;
}

class SupportTestClassOne
{
	use SupportTestTraitTwo;
}

class SupportTestClassTwo extends SupportTestClassOne
{
	//
}

trait SupportTestTraitThree
{
	//
}

class SupportTestClassThree extends SupportTestClassTwo
{
	use SupportTestTraitThree;
}
