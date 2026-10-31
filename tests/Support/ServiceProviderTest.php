<?php
namespace Mantle\Tests\Support;

use Attribute;
use Mantle\Application\Application;
use Mantle\Console\Command;
use Mantle\Contracts\Providers as ProviderContracts;
use Mantle\Events\Dispatcher;
use Mantle\Support\Service_Provider;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Attributes\Filter;
use Mantle\Types\Validator;

class ServiceProviderTest extends \Mockery\Adapter\Phpunit\MockeryTestCase {
	protected function setUp(): void {
		parent::setUp();

		remove_all_actions( 'init' );
		remove_all_filters( 'custom_filter' );
		remove_all_filters( 'custom_filter_dedupe' );
		remove_all_filters( 'custom_filter_from_attribute' );

		Service_Provider::$publishes = [];
		Service_Provider::$publish_tags = [];
	}

	public function test_service_provider_registered(): void {
		$service_provider = $this->getMockBuilder( Service_Provider::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'register', 'boot' ] )
			->getMock();
		$service_provider->expects( $this->once() )->method( 'register' );
		$service_provider->expects( $this->never() )->method( 'boot' );

		$app = new Application_Without_Constructor();
		$app->register( $service_provider );

		$this->assertFalse( $app->is_booted() );
	}

	public function test_service_provider_booted(): void {
		$service_provider = $this->getMockBuilder( Service_Provider::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'register', 'boot' ] )
			->getMock();
		$service_provider->expects( $this->once() )->method( 'register' );
		$service_provider->expects( $this->once() )->method( 'boot' );

		$app = new Application_Without_Constructor();
		$app->register( $service_provider );

		$this->assertFalse( $app->is_booted() );
		$app->boot();
		$this->assertTrue( $app->is_booted() );
	}

	public function test_hook_method_action(): void {
		$_SERVER['__hook_fired'] = false;

		$app = new Application_Without_Constructor();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		do_action( 'custom_hook' );

		$this->assertTrue( $_SERVER['__hook_fired'] );
	}

	public function test_hook_method_action_alternative_name(): void {
		$_SERVER['__hook_fired'] = false;

		$app = new Application_Without_Constructor();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		do_action( 'my_custom_hook' );

		$this->assertTrue( $_SERVER['__hook_fired'] );
	}

	public function test_hook_method_filter(): void {
		$app = new Application_Without_Constructor();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$value = apply_filters( 'custom_filter', 5 );

		$this->assertSame( 15, $value );
	}

	public function test_hook_attribute(): void {
		$app = new Application_Without_Constructor();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		do_action( 'testable-attribute-hook' );

		$this->assertTrue( $_SERVER['__custom_hook_fired'] ?? false );
	}

	public function test_filter_attribute(): void {
		$app = new Application_Without_Constructor();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$value = apply_filters( 'custom_filter_from_attribute', 5 );

		$this->assertSame( 50, $value );
	}

	public function test_hook_attribute_deduplicate(): void {
		$app = new Application_Without_Constructor();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$value = apply_filters( 'custom_filter_dedupe', 0 );

		$this->assertSame( 10, $value );
	}

	public function test_typehint_event(): void {
		$_SERVER['__custom_event_fired'] = false;

		$app = new Application_Without_Constructor();
		$app->register( Provider_Test_Hook::class );
		$app->boot();

		$app['events'] = new Dispatcher( $app );

		$app['events']->dispatch( new Example_Service_Provider_Event() );

		$this->assertInstanceOf( Example_Service_Provider_Event::class, $_SERVER['__custom_event_fired'] );
	}

	public function test_publishable_service_providers(): void {
		$app = new Application_Without_Constructor();
		$app->register( ServiceProviderForTestingOne::class );
		$app->register( ServiceProviderForTestingTwo::class );
		$app->boot();

		$expected = [
			ServiceProviderForTestingOne::class,
			ServiceProviderForTestingTwo::class,
		];

		$this->assertSame( $expected, Service_Provider::publishable_providers() );

		$this->assertSame(
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

		$this->assertSame(
			[
				'source/tagged/one' => 'destination/tagged/one',
				'source/tagged/two/a' => 'destination/tagged/two/a',
				'source/tagged/two/b' => 'destination/tagged/two/b',
			],
			Service_Provider::paths_to_publish(
				tags: 'some_tag',
			),
		);

		$this->assertSame(
			[
				'source/tagged/multiple' => 'destination/tagged/multiple',
			],
			Service_Provider::paths_to_publish(
				tags: 'tag_two',
			),
		);

		$this->assertSame(
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

		$this->assertSame(
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

	public function test_publishable_tags(): void {
		$app = new Application_Without_Constructor();
		$app->register( ServiceProviderForTestingOne::class );
		$app->register( ServiceProviderForTestingTwo::class );
		$app->boot();

		$this->assertSame( [ 'some_tag', 'tag_one', 'tag_two' ], Service_Provider::publishable_tags() );
	}

	public function test_call_after_resolving(): void {
		$_SERVER['__after_resolving'] = false;

		$app = new Application_Without_Constructor();
		$app->register(
			new class ( $app ) extends Service_Provider {
				public function register(): void {
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

		$this->assertFalse( $_SERVER['__after_resolving'] );

		$app->bind( 'foo',  fn () => 'bar' );

		$this->assertSame( 'bar', $app->make( 'foo' ) );

		$this->assertSame( 'one', $_SERVER['__after_resolving'] );
	}

	public function test_call_after_resolving_already_resolved(): void {
		$_SERVER['__after_resolving'] = false;

		$app = new Application_Without_Constructor();
		$app->register(
			new class ( $app ) extends Service_Provider {
				public function register(): void {
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

		$this->assertSame( 'bar', $app->make( 'foo' ) );

		$this->assertSame( 'one', $_SERVER['__after_resolving'] );
	}

	public function test_service_provider_with_passing_validator(): void {
		$_SERVER['__service_provider_booted'] = false;

		$app = new Application_Without_Constructor();

		$app->register( PassingValidatorServiceProvider::class );

		$this->assertFalse( $_SERVER['__service_provider_booted'] );
		$app->boot();
		$this->assertTrue( $_SERVER['__service_provider_booted'] );
	}

	public function test_service_provider_with_failing_validator(): void {
		$_SERVER['__service_provider_booted'] = false;

		$app = new Application_Without_Constructor();

		$app->register( FailingValidatorServiceProvider::class );

		$this->assertFalse( $_SERVER['__service_provider_booted'] );
		$app->boot();
		$this->assertFalse( $_SERVER['__service_provider_booted'] );
	}
}

/**
 * Application that skips its constructor, as a partial mock would.
 */
class Application_Without_Constructor extends Application {
	public function __construct() {}
}

class Provider_Test_Hook extends Service_Provider {
	public function on_custom_hook(): void {
		$_SERVER['__hook_fired'] = true;
	}

	public function action__my_custom_hook(): void {
		$_SERVER['__hook_fired'] = true;
	}

	public function on_custom_filter( int $value ): int {
		return $value + 10;
	}

	#[Action('testable-attribute-hook', 20)]
	public function handle_custom_hook(): void {
		$_SERVER['__custom_hook_fired'] = true;
	}

	// Assert that only a single action is registered for this hook.
	#[Action('custom_filter_dedupe')]
	public function on_custom_filter_dedupe( int $value ): int {
		return $value + 10;
	}

	#[Action(Example_Service_Provider_Event::class)]
	public function handle_custom_event( Example_Service_Provider_Event $event ): void {
		$_SERVER['__custom_event_fired'] = $event;
	}

	#[Filter('custom_filter_from_attribute')]
	public function filter_custom_filter_from_attribute( int $value ): int {
		return $value * 10;
	}
}

class ServiceProviderForTestingOne extends Service_Provider {
	public function boot(): void {
		$this->publishes( [ 'source/unmarked/one' => 'destination/unmarked/one' ] );
		$this->publishes( [ 'source/tagged/one' => 'destination/tagged/one' ], 'some_tag' );
		$this->publishes( [ 'source/tagged/multiple' => 'destination/tagged/multiple' ], [ 'tag_one', 'tag_two' ] );
	}
}

class ServiceProviderForTestingTwo extends Service_Provider {
	public function boot(): void {
		$this->publishes( [ 'source/unmarked/two/a' => 'destination/unmarked/two/a' ] );
		$this->publishes( [ 'source/unmarked/two/b' => 'destination/unmarked/two/b' ] );
		$this->publishes( [ 'source/unmarked/two/c' => 'destination/tagged/two/a' ] );
		$this->publishes( [ 'source/tagged/two/a' => 'destination/tagged/two/a' ], 'some_tag' );
		$this->publishes( [ 'source/tagged/two/b' => 'destination/tagged/two/b' ], 'some_tag' );
	}
}

class Example_Service_Provider_Event {}

#[Attribute]
class PassingValidator implements Validator {
	public function validate(): bool {
		return true;
	}
}

#[PassingValidator]
class PassingValidatorServiceProvider extends Service_Provider {
	public function register(): void {
		$_SERVER['__service_provider_registered'] = true;
	}

	public function boot(): void {
		$_SERVER['__service_provider_booted'] = true;
	}
}

#[Attribute]
class FailingValidator implements Validator {
	public function validate(): bool {
		return false;
	}
}

#[FailingValidator]
class FailingValidatorServiceProvider extends Service_Provider {
	public function register(): void {
		$_SERVER['__service_provider_registered'] = true;
	}

	public function boot(): void {
		$_SERVER['__service_provider_booted'] = true;
	}
}
