<?php
namespace Mantle\Tests\Scheduling;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Contracts\Container;
use Mantle\Scheduling\Event;
use Mantle\Scheduling\Schedule;
use Mantle\Testing\Mock_Http_Response;
use Mockery as m;

class Test_Event extends Framework_Test_Case {
	protected function tearDown(): void {
		parent::tearDown();

		m::close();
	}

	public function test_running_event() {
		$_SERVER['__event_should_run']   = false;
		$_SERVER['__event_shouldnt_run'] = false;

		$schedule = $this->app->make(Schedule::class);
		$schedule
			->call(
				function() {
					$_SERVER['__event_should_run'] = true;
				}
			)
			->when( function() { return true; } );

		// Setup an event that shouldn't be run because of a filter.
		$schedule
			->call(
				function() {
					$_SERVER['__event_shouldnt_run'] = true;
				}
			)
			->skip( function() { return true; } );

		$schedule->run_due_events();

		$this->assertTrue( $_SERVER['__event_should_run'] );
		$this->assertFalse( $_SERVER['__event_shouldnt_run'] );
	}

	public function test_before_after_callback() {
		$_SERVER['__event_before_callback'] = false;
		$_SERVER['__event_after_callback']  = false;

		$schedule = $this->app->make(Schedule::class);
		$schedule
			->call( function() { } )
			->before( function() {
					$_SERVER['__event_before_callback'] = microtime( true );
				}
			)
			->after( function() {
					$_SERVER['__event_after_callback'] = microtime( true );
				}
			);
		$schedule->run_due_events();

		$this->assertTrue( $_SERVER['__event_before_callback'] < $_SERVER['__event_after_callback'] );
	}

	public function testPingRescuesTransferExceptions() {
		$this->fake_request( fn () => Mock_Http_Response::create()->with_status( 500 ) );

		$event = new Event( '' );

		$thenCalled = false;

		$event->pingBefore( 'https://httpstat.us/500' )
			->then(
				function () use ( &$thenCalled ) {
					$thenCalled = true;
				}
			);

		$event->call_before_callbacks( $this->app->make( Container::class ) );
		$event->call_after_callbacks( $this->app->make( Container::class ) );

		$this->assertTrue( $thenCalled );

		$this->assertRequestSent( 'https://httpstat.us/500' );
	}
}
