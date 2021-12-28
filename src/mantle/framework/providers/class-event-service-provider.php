<?php
/**
 * Event_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Contracts\Application;
use Mantle\Support\Service_Provider;
use Mantle\Facade\Event;
use Mantle\Framework\Events\Discover_Events;
use Mantle\Framework\Events\Events_Manifest;

use function Mantle\Framework\Helpers\collect;

/**
 * Event Service Provider
 */
class Event_Service_Provider extends Service_Provider {
	/**
	 * The event listener mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [];

	/**
	 * Register the application's event listeners.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->booting(
			function() {
				$events = $this->get_events();
				foreach ( $events as $event => $listeners ) {
					foreach ( array_unique( $listeners ) as $listener ) {
						[ $listener, $priority ] = $this->parse_listener( $listener );

						Event::listen( $event, $listener, $priority );
					}
				}

				// todo: add event subscribers.
			}
		);

		$this->app->singleton(
			Events_Manifest::class,
			fn ( Application $app ) => new Events_Manifest(
				$app->get_cached_events_path(),
				$app,
			),
		);
	}

	/**
	 * Get discovered events and listeners for the application.
	 *
	 * @return array
	 */
	public function get_events(): array {
		if ( $this->app->is_events_cached() ) {
			$this->app['log']->info( 'Using cached events' );

			return $this->app[ Events_Manifest::class ]->events();
		}

		return array_merge_recursive(
			$this->get_discovered_events(),
			$this->get_listen(),
		);
	}

	/**
	 * Get discovered events for the application.
	 *
	 * @return array
	 */
	protected function get_discovered_events(): array {
		return $this->should_discover_events()
			? $this->discover_events()
			: [];
	}

	/**
	 * Discover events and listeners for the application.
	 *
	 * @return array
	 */
	protected function discover_events(): array {
		return collect( $this->discover_events_within() )
			->reject( fn ( $dir ) => ! is_dir( $dir ) )
			->reduce(
				fn ( array $discovered, string $directory ) => array_merge_recursive(
					$discovered,
					Discover_Events::within( $directory, $this->app->get_base_path() )
				),
				[]
			);
	}

	/**
	 * Get event events and handlers.
	 *
	 * @return array
	 */
	protected function get_listen(): array {
		return $this->listen;
	}

	/**
	 * Flag if Mantle should discover events automatically.
	 *
	 * @return bool
	 */
	public function should_discover_events(): bool {
		return false;
	}

	/**
	 * Get the listener directories that should be used to discover events.
	 *
	 * @return string[]
	 */
	protected function discover_events_within() {
		return [
			$this->app->get_app_path( 'listeners' ),
		];
	}

	/**
	 * Parse an event listener.
	 *
	 * @param mixed $listener Event listener, optionally an array with a listener
	 *                        and priority.
	 * @return array
	 */
	protected function parse_listener( $listener ): array {
		// Support the listener being an array of listener and action priority.
		if ( is_array( $listener ) && isset( $listener[1] ) && is_numeric( $listener[1] ) ) {
			[ $listener, $priority ] = $listener;
		}

		return [ $listener, $priority ?? 10 ];
	}
}
