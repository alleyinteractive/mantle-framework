<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use function Mantle\Framework\Helpers\class_basename;
use function Mantle\Framework\Helpers\class_uses_recursive;
use function Mantle\Framework\Helpers\head;
use function Mantle\Framework\Helpers\last;
use function Mantle\Framework\Helpers\object_get;
use function Mantle\Framework\Helpers\preg_replace_array;
use function Mantle\Framework\Helpers\retry;
use function Mantle\Framework\Helpers\tap;
use function Mantle\Framework\Helpers\throw_if;
use function Mantle\Framework\Helpers\throw_unless;
use function Mantle\Framework\Helpers\transform;
use function Mantle\Framework\Helpers\with;

class SupportHelpersGeneralTest extends TestCase {
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

	public function providesPregReplaceArrayData() {
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
	public function testPregReplaceArray( $pattern, $replacements, $subject, $expectedOutput ) {
		$this->assertSame(
			$expectedOutput,
			preg_replace_array( $pattern, $replacements, $subject )
		);
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

