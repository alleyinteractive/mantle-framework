<?php
/**
 * Lightweight_Event_Dispatcher class file
 *
 * @package Mantle
 */

namespace Mantle\Console\Events;

use Mantle\Events\Dispatcher;
use Mantle\Support\Arr;
use RuntimeException;

/**
 * Lightweight Event Dispatcher
 *
 * This is a lightweight version of the event dispatcher that is used for
 * console isolation mode.
 */
class Lightweight_Event_Dispatcher extends Dispatcher {
	/**
	 * Event listeners.
	 *
	 * @var array
	 */
	protected array $listeners = [];

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string|array    $events
	 * @param  \Closure|string $listener
	 * @param  int             $priority
	 * @return void
	 */
	public function listen( $events, $listener, int $priority = 10 ) {
		foreach ( (array) $events as $event ) {
			$this->listeners[ $event ][ $priority ][] = $this->make_listener( $listener );
		}
	}

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string $event_name
	 * @return bool
	 */
	public function has_listeners( $event_name ): bool {
		return isset( $this->listeners[ $event_name ] );
	}

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string $subscriber
	 * @return void
	 *
	 * @throws RuntimeException Thrown if run.
	 */
	public function subscribe( $subscriber ) {
		throw new RuntimeException( 'Subscribers are not supported in lightweight mode.' );
	}

	/**
	 * Dispatch an event and call the listeners.
	 *
	 * @param  string|object $event Event name.
	 * @param  mixed         $payload Event payload.
	 * @return mixed
	 */
	public function dispatch( $event, $payload = [] ) {
		$filterable_value = is_array( $payload ) ? Arr::first( $payload ) : $payload;

		[ $event, $payload ] = $this->parse_event_and_payload( $event, $payload );

		if ( empty( $this->listeners[ $event ] ) ) {
			return $filterable_value;
		}

		ksort( $this->listeners[ $event ] );

		foreach ( $this->listeners[ $event ] as $listeners ) {
			foreach ( $listeners as $listener ) {
				$filterable_value = $listener( ...$payload );

				// Replace the first payload value with the return value of the listener.
				if ( is_array( $payload ) ) {
					$payload[0] = $filterable_value;
				}
			}
		}

		return $filterable_value;
	}

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param string          $event Event to remove.
	 * @param callable|string $listener Listener to remove.
	 * @param int             $priority Priority of the listener.
	 * @return void
	 */
	public function forget( $event, $listener = null, int $priority = 10 ) {
		if ( empty( $this->listeners[ $event ][ $priority ] ) ) {
			return;
		}

		if ( is_null( $listener ) ) {
			unset( $this->listeners[ $event ][ $priority ] );
		} else {
			$this->listeners[ $event ][ $priority ] = array_filter(
				$this->listeners[ $event ][ $priority ],
				function ( $value ) use ( $listener ) {
					return $value !== $listener;
				}
			);
		}
	}
}
