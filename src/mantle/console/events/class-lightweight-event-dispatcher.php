<?php
/**
 * Lightweight_Event_Dispatcher class file
 *
 * @package Mantle
 */

namespace Mantle\Console\Events;

use Mantle\Events\Dispatcher;
use Mantle\Support\Arr;
use Mantle\Support\Str;
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
	 * @var array<string, array<int, array<callable>>>
	 */
	protected array $listeners = [];

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string|string[] $events
	 * @param  string|callable $listener
	 * @param  int             $priority
	 */
	public function listen( string|array $events, string|callable $listener, int $priority = 10 ): void {
		foreach ( (array) $events as $event ) {
			if ( str_contains( $event, '*' ) ) {
				$this->setup_wildcard_listener( $event, $listener );

				continue;
			}

			$this->listeners[ $event ][ $priority ][] = $this->make_listener( $listener );
		}
	}

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string $event_name
	 */
	public function has_listeners( string $event_name ): bool {
		return ! empty( $this->listeners[ $event_name ] ) || $this->has_wildcard_listeners( $event_name );
	}

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string $subscriber
	 *
	 * @throws RuntimeException Thrown if run.
	 */
	public function subscribe( object|string $subscriber ): void {
		throw new RuntimeException( 'Subscribers are not supported in lightweight mode.' );
	}

	/**
	 * Dispatch an event and call the listeners.
	 *
	 * @throws RuntimeException Thrown if the event is an object and payload is passed.
	 *
	 * @param  string|object $event Event name.
	 * @param  mixed         ...$payload Event payload.
	 */
	public function dispatch( string|object $event, mixed ...$payload ): mixed {
		if ( is_object( $event ) && ! empty( $payload ) ) {
			throw new RuntimeException( 'You cannot pass payload to an object event.' );
		}

		$filterable_value = Arr::first( $payload );

		[ $event, $payload ] = $this->parse_event_and_payload( $event, $payload );

		foreach ( $this->get_listeners( $event ) as $listeners ) {
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
	 * Retrieve all listeners for a given event.
	 *
	 * @param string $event Event name.
	 * @return array<int, array<string|callable>> Arrays of listeners, indexed by priority and sorted.
	 */
	public function get_listeners( string $event ): array {
		$listeners = $this->listeners[ $event ] ?? [];

		$max_index = count( $listeners ) > 0 ? max( array_keys( $listeners ) ) : 0;

		// Add the wildcard listeners at the end of the list. This is to ensure that
		// they are called after the normal listeners who have priority defined.
		$listeners[ $max_index + 1 ] = array_merge(
			$listeners[ $max_index + 1 ] ?? [],
			$this->get_wildcard_listeners( $event ),
		);

		ksort( $listeners );

		return $listeners;
	}

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param object|string        $event Event to remove.
	 * @param callable|string|null $listener Listener to remove.
	 * @param int                  $priority Priority of the listener.
	 */
	public function forget( object|string $event, callable|string|null $listener = null, int $priority = 10 ): void {
		if ( is_object( $event ) ) {
			$event = $event::class;
		}

		if ( str_contains( $event, '*' ) ) {
			$this->forget_wildcard( $event, $listener );

			return;
		}

		if ( empty( $this->listeners[ $event ][ $priority ] ) ) {
			return;
		}

		if ( is_null( $listener ) ) {
			unset( $this->listeners[ $event ][ $priority ] );
		} else {
			$this->listeners[ $event ][ $priority ] = array_filter(
				$this->listeners[ $event ][ $priority ],
				fn ( $value ) => $value !== $listener,
			);
		}

		if ( empty( $this->listeners[ $event ][ $priority ] ) ) {
			unset( $this->listeners[ $event ][ $priority ] );
		}

		if ( empty( $this->listeners[ $event ] ) ) {
			unset( $this->listeners[ $event ] );
		}
	}
}
