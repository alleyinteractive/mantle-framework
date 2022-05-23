<?php
namespace Mantle\Tests\Framework\Tests;

use Mantle\Framework\Events\Discover_Events;
use Mantle\Support\Environment;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Tests\Framework\Events\Fixtures\Events\Event_One;
use Mantle\Tests\Framework\Events\Fixtures\Events\Event_Two;
use Mantle\Tests\Framework\Events\Fixtures\Listeners\Example_Listener;

class Test_Discover_Events extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// Mock a true Mantle application.
		Environment::get_repository()->set( 'APP_NAMESPACE', 'Mantle\\Tests' );
		$this->app->set_app_path( dirname( __DIR__, 2 ) );
	}

	protected function tearDown(): void {
		Environment::get_repository()->clear( 'APP_NAMESPACE' );
		$this->app->set_app_path( $this->app->get_base_path( 'app' ) );
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
				$is_php_8 ? [ Example_Listener::class . '@handle_attribute_event_one', 10 ] : null,
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
			'attribute-event' => $is_php_8 ? [
				[ Example_Listener::class . '@handle_attribute_string_callback', 10 ],
				[ Example_Listener::class . '@handle_attribute_string_callback_priority', 20 ],
			] : null,
		];

		// Filter out expected events for PHP 7.4.
		if ( ! $is_php_8 ) {
			foreach ( $expected as $event => $listeners ) {
				if ( ! $listeners ) {
					unset( $events[ $event ] );
					continue;
				}

				$events[ $event ] = array_filter(
					$events[ $event ],
					fn ( $listener ) => $listener !== null,
				);
			}
		}

		$this->assertEquals( $expected, $events );
	}
}
