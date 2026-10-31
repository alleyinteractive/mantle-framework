<?php

namespace Mantle\Tests\Support;

use BadMethodCallException;
use Error;
use Mantle\Support\Forward_Calls;
use PHPUnit\Framework\TestCase;

class ForwardCallsTest extends TestCase {

	public function testForward_Calls(): void {
		$results = ( new Forward_CallsOne() )->forwardedTwo( 'foo', 'bar' );

		$this->assertSame( array( 'foo', 'bar' ), $results );
	}

	public function testNestedForwardCalls(): void {
		$results = ( new Forward_CallsOne() )->forwardedBase( 'foo', 'bar' );

		$this->assertSame( array( 'foo', 'bar' ), $results );
	}

	public function testMissingForwardedCallThrowsCorrectError(): void {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsOne::missingMethod()' );

		( new Forward_CallsOne() )->missingMethod( 'foo', 'bar' );
	}

	public function testMissingAlphanumericForwardedCallThrowsCorrectError(): void {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsOne::this1_shouldWork_too()' );

		( new Forward_CallsOne() )->this1_shouldWork_too( 'foo', 'bar' );
	}

	public function testNonForwardedErrorIsNotTamperedWith(): void {
		$this->expectException( Error::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsBase::missingMethod()' );

		( new Forward_CallsOne() )->baseError( 'foo', 'bar' );
	}

	public function testthrow_bad_method_call_exception(): void {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsOne::test()' );

		( new Forward_CallsOne() )->throwTestException( 'test' );
	}
}

/**
 * @method mixed forwardedTwo( mixed ...$parameters )
 * @method mixed forwardedBase( mixed ...$parameters )
 * @method mixed missingMethod( mixed ...$parameters )
 * @method mixed this1_shouldWork_too( mixed ...$parameters )
 * @method mixed baseError( mixed ...$parameters )
 */
class Forward_CallsOne {

	use Forward_Calls;

	/**
	 * @param string       $method Method name.
	 * @param array<mixed> $parameters Method parameters.
	 */
	public function __call( string $method, array $parameters ): mixed {
		return $this->forward_call_to( new Forward_CallsTwo(), $method, $parameters );
	}

	public function throwTestException( string $method ): void {
		static::throw_bad_method_call_exception( $method );
	}
}

class Forward_CallsTwo {

	use Forward_Calls;

	/**
	 * @param string       $method Method name.
	 * @param array<mixed> $parameters Method parameters.
	 */
	public function __call( string $method, array $parameters ): mixed {
		return $this->forward_call_to( new Forward_CallsBase(), $method, $parameters );
	}

	/**
	 * @param mixed ...$parameters Parameters.
	 * @return array<int|string, mixed>
	 */
	public function forwardedTwo( mixed ...$parameters ): array {
		return $parameters;
	}
}

class Forward_CallsBase {

	private string $missing_method = 'missingMethod';

	/**
	 * @param mixed ...$parameters Parameters.
	 * @return array<int|string, mixed>
	 */
	public function forwardedBase( mixed ...$parameters ): array {
		return $parameters;
	}

	public function baseError(): mixed {
		return $this->{$this->missing_method}();
	}
}
