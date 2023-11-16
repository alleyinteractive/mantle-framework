<?php
namespace Mantle\Tests\Support;

use Mantle\Application\Application;
use Mantle\Console\Command;
use Mantle\Contracts\Providers as ProviderContracts;
use Mantle\Events\Dispatcher;
use Mantle\Support\Service_Provider;
use Mantle\Support\Attributes\Action;
use Mockery as m;

class Test_Service_Provider extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	protected function setUp(): void {
		parent::setUp();

		remove_all_actions( 'init' );
		remove_all_filters( 'custom_filter' );
		remove_all_filters( 'custom_filter_dedupe' );

		Service_Provider::$publishes = [];
		Service_Provider::$publish_tags = [];
	}

	public function test_service_provider_registered() {
		$service_provider = m::mock( Service_Provider::class )->makePartial();
		$service_provider->shouldReceive( 'register' )->once();
		$service_provider->shouldNotReceive( 'boot' );

		$app = m::mock( Application::class )->makePartial();
		$app->register( $service_provider );

		$this->assertFalse( $app->is_booted() );
	}

	public function test_service_provider_booted() {
		$service_provider = m::mock( Service_Provider::class )->makePartial();
		$service_provider->shouldReceive( 'register' )->once();
		$service_provider->shouldReceive( 'boot' )->once();

		$app = m::mock( Application::class )->makePartial();
		$app->register( $service_provider );

		$this->assertFalse( $app->is_booted() );
		$app->boot();
		$this->assertTrue( $app->is_booted() );
	}

	public function test_hook_method_action() {
		$_SERVER['__hook_fired'] = false;

		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		do_action( 'custom_hook' );

		$this->assertTrue( $_SERVER['__hook_fired'] );
	}

	public function test_hook_method_filter() {
		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$value = apply_filters( 'custom_filter', 5 );

		$this->assertEquals( 15, $value );
	}

	public function test_hook_attribute() {
		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		do_action( 'testable-attribute-hook' );

		$this->assertTrue( $_SERVER['__custom_hook_fired'] ?? false );
	}

	public function test_hook_attribute_deduplicate() {
		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$value = apply_filters( 'custom_filter_dedupe', 0 );

		$this->assertEquals( 10, $value );
	}

	public function test_typehint_event() {
		$_SERVER['__custom_event_fired'] = false;

		$app = m::mock( Application::class )->makePartial();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$app['events'] = new Dispatcher( $app );

		$app['events']->dispatch( new Example_Service_Provider_Event() );

		$this->assertInstanceOf( Example_Service_Provider_Event::class, $_SERVER['__custom_event_fired'] );
	}

	public function test_publishable_service_providers() {
		$app = m::mock( Application::class )->makePartial();
		$app->register( ServiceProviderForTestingOne::class );
		$app->register( ServiceProviderForTestingTwo::class );
		$app->boot();

		$expected = [
			ServiceProviderForTestingOne::class,
			ServiceProviderForTestingTwo::class,
		];

		$this->assertEquals( $expected, Service_Provider::publishable_providers() );

		$this->assertEquals(
			[
				'source/unmarked/one' => 'destination/unmarked/one',
				'source/tagged/one' => 'destination/tagged/one',
				'source/tagged/multiple' => 'destination/tagged/multiple',
			],
			Service_Provider::paths_to_publish(
				providers: ServiceProviderForTestingOne::class,
			),
		);

		$this->assertEquals(
			[
				'source/unmarked/two/a' => 'destination/unmarked/two/a',
				'source/unmarked/two/b' => 'destination/unmarked/two/b',
				'source/unmarked/two/c' => 'destination/tagged/two/a',
				'source/tagged/two/b' => 'destination/tagged/two/b',
				'source/tagged/two/a' => 'destination/tagged/two/a',
			],
			Service_Provider::paths_to_publish(
				providers: ServiceProviderForTestingTwo::class,
			),
		);

		$this->assertEquals(
			[
				'source/tagged/one' => 'destination/tagged/one',
				'source/tagged/two/a' => 'destination/tagged/two/a',
				'source/tagged/two/b' => 'destination/tagged/two/b',
			],
			Service_Provider::paths_to_publish(
				tags: 'some_tag',
			),
		);

		$this->assertEquals(
			[
				'source/tagged/multiple' => 'destination/tagged/multiple',
			],
			Service_Provider::paths_to_publish(
				tags: 'tag_two',
			),
		);

		$this->assertEquals(
			[
				'source/tagged/one' => 'destination/tagged/one'
			],
			Service_Provider::paths_to_publish(
				providers: ServiceProviderForTestingOne::class,
				tags: 'some_tag',
			),
		);

		$this->assertEquals(
			[
				'source/tagged/one' => 'destination/tagged/one',
				'source/tagged/multiple' => 'destination/tagged/multiple',
				'source/tagged/two/a' => 'destination/tagged/two/a',
				'source/tagged/two/b' => 'destination/tagged/two/b',
			],
			Service_Provider::paths_to_publish(
				tags: [ 'some_tag', 'tag_two' ],
			),
		);

		$this->assertEquals(
			[
				'source/tagged/two/a' => 'destination/tagged/two/a',
				'source/tagged/two/b' => 'destination/tagged/two/b',
			],
			Service_Provider::paths_to_publish(
				providers: ServiceProviderForTestingTwo::class,
				tags: [ 'some_tag', 'tag_two' ],
			),
		);
	}

	public function test_publishable_tags() {
		$app = m::mock( Application::class )->makePartial();
		$app->register( ServiceProviderForTestingOne::class );
		$app->register( ServiceProviderForTestingTwo::class );
		$app->boot();

		$this->assertEquals( [ 'some_tag', 'tag_one', 'tag_two' ], Service_Provider::publishable_tags() );
	}

	public function test_call_after_resolving() {
		$_SERVER['__after_resolving'] = false;

		$app = m::mock( Application::class )->makePartial();
		$app->register(
			new class ( $app ) extends Service_Provider {
				public function register() {
					$this->call_after_resolving(
						'foo',
						function ( $resolved ) {
							$_SERVER['__after_resolving'] = 'one';
						}
					);
				}
			}
		);

		$app->boot();

		$this->assertEquals( false, $_SERVER['__after_resolving'] );

		$app->bind( 'foo',  fn () => 'bar' );

		$this->assertEquals( 'bar', $app->make( 'foo' ) );

		$this->assertEquals( 'one', $_SERVER['__after_resolving'] );
	}

	public function test_call_after_resolving_already_resolved() {
		$_SERVER['__after_resolving'] = false;

		$app = m::mock( Application::class )->makePartial();
		$app->register(
			new class ( $app ) extends Service_Provider {
				public function register() {
					$this->call_after_resolving(
						'foo',
						function ( $resolved ) {
							$_SERVER['__after_resolving'] = 'one';
						}
					);
				}
			}
		);

		$app->bind( 'foo',  fn () => 'bar' );

		$this->assertEquals( 'bar', $app->make( 'foo' ) );

		$this->assertEquals( 'one', $_SERVER['__after_resolving'] );
	}
}

class Provider_Test_Hook extends Service_Provider {
	public function on_custom_hook() {
		$_SERVER['__hook_fired'] = true;
	}

	public function on_custom_filter( $value ) {
		return $value + 10;
	}

	#[Action('testable-attribute-hook', 20)]
	public function handle_custom_hook() {
		$_SERVER['__custom_hook_fired'] = true;
	}

	// Assert that only a single action is registered for this hook.
	#[Action('custom_filter_dedupe')]
	public function on_custom_filter_dedupe( $value ) {
		return $value + 10;
	}

	#[Action(Example_Service_Provider_Event::class)]
	public function handle_custom_event( Example_Service_Provider_Event $event ) {
		$_SERVER['__custom_event_fired'] = $event;
	}
}

class ServiceProviderForTestingOne extends Service_Provider {
	public function boot() {
		$this->publishes( [ 'source/unmarked/one' => 'destination/unmarked/one' ] );
		$this->publishes( [ 'source/tagged/one' => 'destination/tagged/one' ], 'some_tag' );
		$this->publishes( [ 'source/tagged/multiple' => 'destination/tagged/multiple' ], [ 'tag_one', 'tag_two' ] );
	}
}

class ServiceProviderForTestingTwo extends Service_Provider {
	public function boot() {
		$this->publishes( [ 'source/unmarked/two/a' => 'destination/unmarked/two/a' ] );
		$this->publishes( [ 'source/unmarked/two/b' => 'destination/unmarked/two/b' ] );
		$this->publishes( [ 'source/unmarked/two/c' => 'destination/tagged/two/a' ] );
		$this->publishes( [ 'source/tagged/two/a' => 'destination/tagged/two/a' ], 'some_tag' );
		$this->publishes( [ 'source/tagged/two/b' => 'destination/tagged/two/b' ], 'some_tag' );
	}
}

class Example_Service_Provider_Event {

}
