<?php
namespace Mantle\Tests\Framework;

use Mantle\Application\Application;
use Mantle\Contracts;
use Mantle\Queue\Dispatchable;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Providers\WordPress\Provider;
use Mantle\Queue\Queueable;
use Mantle\Testing\Framework_Test_Case;

class Test_Featherkit extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		global $featherkit;

		$featherkit = null;
	}

	public function test_instantiate_application() {
		$app = featherkit();

		$this->assertInstanceOf( Application::class, $app );

		$this->assertInstanceOf( Contracts\Application::class, $app['app'] );
		$this->assertInstanceOf( Contracts\Config\Repository::class, $app['config'] );
		$this->assertInstanceOf( Contracts\Filesystem\Filesystem_Manager::class, $app['filesystem'] );
		$this->assertInstanceOf( Contracts\Queue\Queue_Manager::class, $app['queue'] );
		$this->assertInstanceOf( Contracts\Events\Dispatcher::class, $app['events'] );
	}

	public function test_application_config() {
		$app = featherkit( [
			'foo' => 'bar',
		] );

		$this->assertEquals( 'bar', $app['config']['foo'] );
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

	// public function test_http_router() {}
}

class Testable_Featherkit_Job implements Contracts\Queue\Job, Contracts\Queue\Can_Queue {
	use Queueable, Dispatchable;

	public function handle() {
		$_SERVER['__example_job'] = true;
	}
}
