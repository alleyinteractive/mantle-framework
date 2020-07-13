<?php
namespace Mantle\Tests\Framework\Scheduling;

use Mantle\Framework\Testing\Test_Case;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mantle\Framework\Contracts\Container;
use Mantle\Framework\Contracts\Exceptions\Handler;
use Mantle\Framework\Scheduling\Event;
use Mantle\Framework\Scheduling\Schedule;
use Mockery as m;

class Test_Event extends Test_Case {
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
		$this->spy( Handler::class )
			->shouldReceive( 'report' )
			->once()
			->with( m::type( ServerException::class ) );

		$httpMock = new HttpClient(
			array(
				'handler' => HandlerStack::create(
					new MockHandler( array( new Psr7Response( 500 ) ) )
				),
			)
		);

		$this->swap( HttpClient::class, $httpMock );

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
	}
}
