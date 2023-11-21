<?php
namespace Mantle\Tests\Framework;

use Mantle\Application\Application;
use Mantle\Support\Service_Provider;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Test_Application extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	protected function tearDown(): void {
		parent::tearDown();

		unset( $_ENV['APP_ENV'] );
	}

	public function test_environment() {
		$_ENV['APP_ENV'] = 'test-env';

		$app = new Application();

		$this->assertEquals( 'test-env', $app->environment() );

		$_ENV['APP_ENV'] = 'another-test-env';

		$this->assertEquals( 'another-test-env', $app->environment() );
	}

	public function test_is_environment() {
		$_ENV['APP_ENV'] = 'test-env';

		$app = new Application();

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
		$this->assertTrue( $_SERVER['__booting_callback'] <= $_SERVER['__boot_callback'] );
	}

	public function test_terminating_callback() {
		$_SERVER['__terminating_callback'] = false;

		$app = new Application();

		$app->flush();
		$app->terminating(
			fn() => $_SERVER['__terminating_callback'] = microtime( true ),
		);

		$app->terminate();

		$this->assertNotFalse( $_SERVER['__terminating_callback'] );
	}

	public function test_service_provider_instance() {
		$app = new Application();
		$app->register( Test_App_Service_Provider::class );

		$provider = $app->get_provider( Test_App_Service_Provider::class );
		$this->assertInstanceOf( Test_App_Service_Provider::class, $provider );

		// Ensure it is a global instance.
		$this->assertSame( $provider, $app->get_provider( Test_App_Service_Provider::class ) );
		$this->assertNull( $app->get_provider( \Invalid_Class::class ) );
	}

	public function test_is_running_in_console() {
		unset( $_ENV['APP_RUNNING_IN_CONSOLE'] );
		$this->assertFalse( ( new Application() )->is_running_in_console() );

		$_ENV['APP_RUNNING_IN_CONSOLE'] = true;

		$this->assertTrue( ( new Application() )->is_running_in_console() );

		unset( $_ENV['APP_RUNNING_IN_CONSOLE'] );
	}

	public function test_can_load_env_file() {
		$this->assertNull( $_ENV['ENV_VAR_FOO'] ?? null );

		$app = new Application();
		$app->environment_path( __DIR__ . '/../fixtures/config' );
		$app->environment_file( 'env-file' );
		$app->load_environment_variables();

		$this->assertEquals( 'bar', environment( 'ENV_VAR_FOO' ) );
		$this->assertEquals( 'bar', $_ENV['ENV_VAR_FOO'] );
		$this->assertEquals( 'bar', $_SERVER['ENV_VAR_FOO'] );
	}

	public function test_can_fail_loading_config_silently() {
		$this->expectOutputString( '' );

		$app = new Application();
		$app->environment_path( __DIR__ . '/../fixtures/config' );
		$app->environment_file( 'fake-file' );
		$app->load_environment_variables();
	}
}


class Test_App_Service_Provider extends Service_Provider { }
