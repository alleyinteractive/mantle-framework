<?php

namespace Mantle\Tests\Support;

use BadMethodCallException;
use Error;
use Mantle\Support\Forward_Calls;
use PHPUnit\Framework\TestCase;

class Test_Forward_Calls extends TestCase {

	public function testForward_Calls() {
		$results = ( new Forward_CallsOne() )->forwardedTwo( 'foo', 'bar' );

		$this->assertEquals( array( 'foo', 'bar' ), $results );
	}

	public function testNestedForwardCalls() {
		$results = ( new Forward_CallsOne() )->forwardedBase( 'foo', 'bar' );

		$this->assertEquals( array( 'foo', 'bar' ), $results );
	}

	public function testMissingForwardedCallThrowsCorrectError() {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsOne::missingMethod()' );

		( new Forward_CallsOne() )->missingMethod( 'foo', 'bar' );
	}

	public function testMissingAlphanumericForwardedCallThrowsCorrectError() {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsOne::this1_shouldWork_too()' );

		( new Forward_CallsOne() )->this1_shouldWork_too( 'foo', 'bar' );
	}

	public function testNonForwardedErrorIsNotTamperedWith() {
		$this->expectException( Error::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsBase::missingMethod()' );

		( new Forward_CallsOne() )->baseError( 'foo', 'bar' );
	}

	public function testthrow_bad_method_call_exception() {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Call to undefined method Mantle\Tests\Support\Forward_CallsOne::test()' );

		( new Forward_CallsOne() )->throwTestException( 'test' );
	}
}

class Forward_CallsOne {

	use Forward_Calls;

	public function __call( $method, $parameters ) {
		return $this->forward_call_to( new Forward_CallsTwo(), $method, $parameters );
	}

	public function throwTestException( $method ) {
		static::throw_bad_method_call_exception( $method );
	}
}

class Forward_CallsTwo {

	use Forward_Calls;

	public function __call( $method, $parameters ) {
		return $this->forward_call_to( new Forward_CallsBase(), $method, $parameters );
	}

	public function forwardedTwo( ...$parameters ) {
		return $parameters;
	}
}

class Forward_CallsBase {

	public function forwardedBase( ...$parameters ) {
		return $parameters;
	}

	public function baseError() {
		return $this->missingMethod();
	}
}
