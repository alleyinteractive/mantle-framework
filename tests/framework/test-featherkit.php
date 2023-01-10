<?php
namespace Mantle\Tests\Framework;

use Mantle\Application\Application;
use Mantle\Contracts;
use Mantle\Events\Dispatcher;
use Mantle\Http\Routing\Router;
use Mantle\Http\Request;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Providers\WordPress\Provider;
use Mantle\Queue\Queueable;
use Mantle\Testing\Test_Case;

class Test_Featherkit extends Test_Case {
	protected function setUp(): void {
		parent::setUp();

		featherkit_clear();
	}

	protected function tearDown(): void {
		parent::tearDown();

		featherkit_clear();
	}

	/**
	 * Creates the application.
	 *
	 * @return \Mantle\Contracts\Application
	 */
	public function create_application(): \Mantle\Contracts\Application {
		return featherkit();
	}

	public function test_instantiate_application() {
		$app = featherkit();

		$this->assertEquals( $this->app, $app );
		$this->assertInstanceOf( Application::class, $app );
		$this->assertInstanceOf( Application::class, $this->app );

		$this->assertInstanceOf( Contracts\Application::class, $app['app'] );
		$this->assertInstanceOf( Contracts\Config\Repository::class, $app['config'] );
		$this->assertInstanceOf( Contracts\Filesystem\Filesystem_Manager::class, $app['filesystem'] );
		$this->assertInstanceOf( Contracts\Queue\Queue_Manager::class, $app['queue'] );
		$this->assertInstanceOf( Contracts\Events\Dispatcher::class, $app['events'] );
	}

	public function test_application_config() {
		featherkit_clear();

		$app = featherkit( [
			'foo' => 'bar',
		] );

		$this->assertEquals( 'bar', $app['config']['foo'] );
	}

	public function test_base_path() {
		$this->assertNotEquals( __DIR__, $this->app->get_base_path() );

		featherkit_clear();

		$app = featherkit(
			base_path: __DIR__,
		);

		$this->assertEquals( __DIR__, $app->get_base_path() );
	}

	public function test_root_url() {
		$this->assertNotEquals( 'https://root.test', $this->app->get_root_url() );

		featherkit_clear();

		$app = featherkit(
			root_url: 'https://root.test',
		);

		$this->assertEquals( 'https://root.test', $app->get_root_url() );
	}

	public function test_dispatch_events() {
		$this->expectApplied( 'example_hook' );

		featherkit()['events']->dispatch( 'example_hook' );
	}

	public function test_queued_job() {
		$app = featherkit();

		$app['events']->forget( Run_Complete::class );

		$_SERVER['__example_job'] = false;

		Provider::on_init();

		Testable_Featherkit_Job::dispatch();

		$this->assertInCronQueue( Testable_Featherkit_Job::class );

		$this->assertFalse( $_SERVER['__example_job'] );

		$this->dispatch_queue();

		$this->assertTrue( $_SERVER['__example_job'] );
	}

	public function test_http_router() {
		$router = $this->get_router();

		$router->get( 'example/route', fn () => 'response' );

		$this->assertEquals( 'response', $router->dispatch( Request::create( 'example/route' ) )->getContent() );
	}

	public function test_make_request() {
		$this->get( '/' )
			->assertOk()
			->assertQueryTrue( 'is_home', 'is_front_page' );
	}

	protected function get_router(): Router {
		$events = new Dispatcher( $this->app );
		$router = new Router( $events, $this->app );

		$this->app->instance( 'request', new Request() );
		$this->app->instance( \Mantle\Contracts\Http\Routing\Router::class, $router );

		return $router;
	}
}

class Testable_Featherkit_Job implements Contracts\Queue\Job, Contracts\Queue\Can_Queue {
	use Queueable, Dispatchable;

	public function handle() {
		$_SERVER['__example_job'] = true;
	}
}
