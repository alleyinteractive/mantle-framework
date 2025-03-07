<?php
namespace Mantle\Tests\Framework\Tests;

use Mantle\Framework\Events\Discover_Events;
use Mantle\Support\Environment;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Tests\Framework\Events\Fixtures\Events\Event_One;
use Mantle\Tests\Framework\Events\Fixtures\Events\Event_Two;
use Mantle\Tests\Framework\Events\Fixtures\Listeners\Example_Listener;

use function Mantle\Support\Helpers\collect;

class DiscoverEventsTest extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// Mock a true Mantle application.
		$this->app['config']->set( 'app.namespace', 'Mantle\\Tests' );
		$this->app->set_app_path( dirname( __DIR__, 2 ) );

		require_once __DIR__ . '/fixtures/listeners/class-example-listener.php';
		require_once __DIR__ . '/fixtures/events/class-event-one.php';
		require_once __DIR__ . '/fixtures/events/class-event-two.php';
	}

	protected function tearDown(): void {
		$this->app->set_app_path( $this->app->get_base_path( 'app' ) );
		$this->app['config']->set( 'app.namespace', 'App' );

		parent::tearDown();
	}

	public function test_events_can_be_discovered() {
		$this->assertEquals( 'Mantle\\Tests', $this->app->get_namespace() );

		$this->assertTrue( class_exists( Example_Listener::class ) );
		$this->assertTrue( class_exists( Event_One::class ) );

		$events = Discover_Events::within(
			__DIR__ . '/fixtures/listeners',
			getcwd(),
		);

		$is_php_8 = version_compare( PHP_VERSION, '8.0.0', '>=' );

		$expected = [
			// Type hinted events.
			Event_One::class => [
				[ Example_Listener::class . '@handle', 10 ],
				[ Example_Listener::class . '@handle_event_one', 10 ],
				[ Example_Listener::class . '@handle_attribute_event_one', 10 ],
			],
			Event_Two::class => [
				[ Example_Listener::class . '@handle_event_two', 10 ],
				[ Example_Listener::class . '@handle_event_two_at_20', 20 ],
			],
			// WordPress non-type hinted events.
			'wp_loaded' => [
				[ Example_Listener::class . '@on_wp_loaded', 10 ],
			],
			'pre_get_posts' => [
				[ Example_Listener::class . '@on_pre_get_posts_at_20', 20 ],
			],
			'attribute-event' => [
				[ Example_Listener::class . '@handle_attribute_string_callback', 10 ],
				[ Example_Listener::class . '@handle_attribute_string_callback_priority', 20 ],
			],
		];

		$this->assertEquals( $expected, $events );
	}
}
