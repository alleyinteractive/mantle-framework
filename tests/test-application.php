<?php
namespace Mantle\Tests;

use Mantle\Framework\Application;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Test_Application extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	public function test_environment() {
		$app = new Application();

		$_ENV['env'] = 'test-env';
		$this->assertEquals( 'test-env', $app->environment() );

		$_ENV['env'] = 'another-test-env';
		$this->assertEquals( 'another-test-env', $app->environment() );
	}

	public function test_is_environment() {
		$_ENV['env'] = 'test-env';
		$app         = new Application();

		$this->assertTrue( $app->is_environment( 'test-env', 'another-thing' ) );
		$this->assertTrue( $app->is_environment( 'test-env' ) );
		$this->assertFalse( $app->is_environment( 'not-the-correct-env' ) );
	}

	public function test_abort_404() {
		$app = new Application();
		$this->expectException( NotFoundHttpException::class );
		$this->expectExceptionMessage( 'Not found message' );

		$app->abort( 404, 'Not found message' );
	}

	public function test_abort_500() {
		$app = new Application();
		$this->expectException( HttpException::class );
		$this->expectExceptionMessage( 'Something went wrong' );

		$app->abort( 500, 'Something went wrong' );
	}
}
