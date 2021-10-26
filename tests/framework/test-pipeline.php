<?php

namespace Mantle\Tests\Framework;

use Mantle\Container\Container;
use Mantle\Support\Pipeline;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PipelineTest extends TestCase {

	public function testPipelineBasicUsage() {
		$pipeTwo = function ( $piped, $next ) {
			$_SERVER['__test.pipe.two'] = $piped;

			return $next( $piped );
		};

		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( PipelineTestPipeOne::class, $pipeTwo ) )
			->then(
				function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.two'] );

		unset( $_SERVER['__test.pipe.one'] );
		unset( $_SERVER['__test.pipe.two'] );
	}

	public function testPipelineUsageWithObjects() {
		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( new PipelineTestPipeOne() ) )
			->then(
				function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}

	public function testPipelineUsageWithInvokableObjects() {
		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( new PipelineTestPipeTwo() ) )
			->then(
				function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}

	public function testPipelineUsageWithCallable() {
		$function = function ( $piped, $next ) {
			$_SERVER['__test.pipe.one'] = 'foo';

			return $next( $piped );
		};

		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( $function ) )
			->then(
				function ( $piped ) {
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

	public function testPipelineUsageWithInvokableClass() {
		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( array( PipelineTestPipeTwo::class ) )
			->then(
				function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertSame( 'foo', $_SERVER['__test.pipe.one'] );

		unset( $_SERVER['__test.pipe.one'] );
	}

	public function testPipelineUsageWithParameters() {
		$parameters = array( 'one', 'two' );

		$result = ( new Pipeline( new Container() ) )
			->send( 'foo' )
			->through( PipelineTestParameterPipe::class . ':' . implode( ',', $parameters ) )
			->then(
				function ( $piped ) {
					return $piped;
				}
			);

		$this->assertSame( 'foo', $result );
		$this->assertEquals( $parameters, $_SERVER['__test.pipe.parameters'] );

		unset( $_SERVER['__test.pipe.parameters'] );
	}

	public function testPipelineViaChangesTheMethodBeingCalledOnThePipes() {
		$pipelineInstance = new Pipeline( new Container() );
		$result           = $pipelineInstance->send( 'data' )
			->through( PipelineTestPipeOne::class )
			->via( 'differentMethod' )
			->then(
				function ( $piped ) {
					return $piped;
				}
			);
		$this->assertSame( 'data', $result );
	}

	public function testPipelineThrowsExceptionOnResolveWithoutContainer() {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'A container instance has not been passed to the Pipeline.' );

		( new Pipeline() )->send( 'data' )
			->through( PipelineTestPipeOne::class )
			->then(
				function ( $piped ) {
					return $piped;
				}
			);
	}

	public function testPipelineThenReturnMethodRunsPipelineThenReturnsPassable() {
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

	public function handle( $piped, $next ) {
		$_SERVER['__test.pipe.one'] = $piped;

		return $next( $piped );
	}

	public function differentMethod( $piped, $next ) {
		return $next( $piped );
	}
}

class PipelineTestPipeTwo {

	public function __invoke( $piped, $next ) {
		$_SERVER['__test.pipe.one'] = $piped;

		return $next( $piped );
	}
}

class PipelineTestParameterPipe {

	public function handle( $piped, $next, $parameter1 = null, $parameter2 = null ) {
		$_SERVER['__test.pipe.parameters'] = array( $parameter1, $parameter2 );

		return $next( $piped );
	}
}
