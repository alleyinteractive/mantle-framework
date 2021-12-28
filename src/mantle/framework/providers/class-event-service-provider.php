<?php
/**
 * Event_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Events\Dispatcher;
use Mantle\Support\Service_Provider;
use Mantle\Facade\Event;

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
	}

	/**
	 * Get discovered events and listeners for the application.
	 *
	 * @return array
	 */
	public function get_events(): array {
		return array_merge_recursive(
			$this->get_discovered_events(),
			$this->get_listen(),
		);
	}

	/**
	 * Get discovered events.
	 *
	 * @return array
	 */
	protected function get_discovered_events(): array {
		// todo: add cached discovered events.
		return [];
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
