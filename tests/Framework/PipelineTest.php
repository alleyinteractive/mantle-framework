<?php

namespace Mantle\Tests\Framework;

use Mantle\Container\Container;
use Mantle\Support\Pipeline;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PipelineTest extends TestCase {

	public function testPipelineBasicUsage(): void {
		$pipeTwo = static function ( $piped, $next ) {
			$_SERVER['__test.pipe.two'] = $piped;

			return $next( $piped );
		};

		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( PipelineTestPipeOne::class, $pipeTwo ) )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.two'] );

		unset( $_SERVER['__test.pipe.one'] );
		unset( $_SERVER['__test.pipe.two'] );
	}

	public function testPipelineUsageWithObjects(): void {
		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( new PipelineTestPipeOne() ) )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}

	public function testPipelineUsageWithInvokableObjects(): void {
		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( new PipelineTestPipeTwo() ) )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}

	public function testPipelineUsageWithCallable(): void {
		$function = static function ( $piped, $next ) {
			$_SERVER['__test.pipe.one'] = 'foo';

			return $next( $piped );
		};

		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( $function ) )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );

		$result = ( new Pipeline( new Container() ) )
			->send( 'bar' )
			->through( $function )
			->thenReturn();

		$this->assertSame( 'bar', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}

	public function testPipelineUsageWithInvokableClass(): void {
		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( PipelineTestPipeTwo::class ) )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}

	public function testPipelineUsageWithParameters(): void {
		$parameters = array( 'one', 'two' );

		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( PipelineTestParameterPipe::class . ':' . implode( ',', $parameters ) )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( $parameters, $_SERVER['__test.pipe.parameters'] );

		unset( $_SERVER['__test.pipe.parameters'] );
	}

	public function testPipelineViaChangesTheMethodBeingCalledOnThePipes(): void {
		$pipelineInstance = new Pipeline( new Container() );
		$result           = $pipelineInstance->send( 'data' )
			->through( PipelineTestPipeOne::class )
			->via( 'differentMethod' )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);
		$this->assertSame( 'data', $result );
	}

	public function testPipelineThrowsExceptionOnResolveWithoutContainer(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'A container instance has not been passed to the Pipeline.' );

		( new Pipeline() )->send( 'data' )
			->through( PipelineTestPipeOne::class )
			->then(
				static function ( $piped ) {
					return $piped;
				}
			);
	}

	public function testPipelineThenReturnMethodRunsPipelineThenReturnsPassable(): void {
		$result = ( new Pipeline( new Container() ) )
					->send( 'foo' )
					->through( array( PipelineTestPipeOne::class ) )
					->thenReturn();

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}
}

class PipelineTestPipeOne {

	public function handle( mixed $piped, callable $next ): mixed {
		$_SERVER['__test.pipe.one'] = $piped;

		return $next( $piped );
	}

	public function differentMethod( mixed $piped, callable $next ): mixed {
		return $next( $piped );
	}
}

class PipelineTestPipeTwo {

	public function __invoke( mixed $piped, callable $next ): mixed {
		$_SERVER['__test.pipe.one'] = $piped;

		return $next( $piped );
	}
}

class PipelineTestParameterPipe {

	public function handle( mixed $piped, callable $next, mixed $parameter1 = null, mixed $parameter2 = null ): mixed {
		$_SERVER['__test.pipe.parameters'] = array( $parameter1, $parameter2 );

		return $next( $piped );
	}
}
