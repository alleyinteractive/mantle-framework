<?php
namespace Mantle\Tests;

use Mantle\Framework\Application;
use Mantle\Framework\Service_Provider;
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

	public function test_boot_callback() {
		$_SERVER['__booting_callback'] = false;
		$_SERVER['__boot_callback']    = false;

		$app = new Application();
		$app->flush();
		$app->booting(
			function() {
				$_SERVER['__booting_callback'] = microtime( true );
			}
		);

		$app->booted(
			function() {
				$_SERVER['__boot_callback'] = microtime( true );
			}
		);

		$app->boot();

		$this->assertNotFalse( $_SERVER['__booting_callback'] );
		$this->assertNotFalse( $_SERVER['__boot_callback'] );

		// Assert that the booting callback happened before the boot one.
		$this->assertTrue( $_SERVER['__booting_callback'] < $_SERVER['__boot_callback'] );
	}

	public function test_service_provider_instance() {
		$app = new Application();
		$app->register( Test_Service_Provider::class );

		$provider = $app->get_provider( Test_Service_Provider::class );
		$this->assertInstanceOf( Test_Service_Provider::class, $provider );

		// Ensure it is a global instance.
		$this->assertSame( $provider, $app->get_provider( Test_Service_Provider::class ) );
		$this->assertNull( $app->get_provider( \Invalid_Class::class ) );
	}
}


class Test_Service_Provider extends Service_Provider { }
