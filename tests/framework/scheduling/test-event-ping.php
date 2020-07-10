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
use Mockery as m;

class Test_Event_Ping extends Test_Case {
	protected function tearDown(): void {
		parent::tearDown();

		m::close();
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
