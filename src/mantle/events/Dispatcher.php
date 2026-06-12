<?php
/**
 * Dispatcher class file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Events;

use Closure;
use Mantle\Contracts\Container;
use Mantle\Contracts\Events\Dispatcher as Dispatcher_Contract;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use RuntimeException;

/**
 * Event Dispatcher
 *
 * @todo Add queued event listeners.
 */
class Dispatcher implements Dispatcher_Contract {
	use WordPress_Action;

	/**
	 * The registered event listeners.
	 *
	 * @var array<string, mixed>
	 */
	protected array $listeners = [];

	/**
	 * The IoC container instance.
	 */
	protected Container $container;

	/**
	 * Wildcard listeners.
	 *
	 * @var array<string, array<string|callable>>
	 */
	protected array $wildcard_listeners = [];

	/**
	 * Wildcard listener lookup cache.
	 *
	 * @var array<string, array<string|callable>>
	 */
	protected array $wildcard_cache = [];

	/**
	 * Create a new event dispatcher instance.
	 *
	 * @param Container|null $container Container instance.
	 */
	public function __construct( ?Container $container = null ) {
		$this->container = $container ?: new \Mantle\Container\Container();
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @todo Add wildcard listeners.
	 *
	 * @param string|string[] $events Event(s) to listen to.
	 * @param string|callable        $listener Listener to register.
	 * @param int          $priority Event priority.
	 * @param  \Closure|string $listener Listener callback.
	 */
	public function listen( string|array $events, string|callable $listener, int $priority = 10 ): void {
		foreach ( (array) $events as $event ) {
			if ( str_contains( $event, '*' ) ) {
				$this->setup_wildcard_listener( $event, $listener );

				continue;
			}

			add_action(
				$event,
				$this->make_listener( $listener ),
				$priority,
				999,
			);
		}
	}

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string $event_name Event name.
	 */
	public function has_listeners( string $event_name ): bool {
		return has_filter( $event_name ) || $this->has_wildcard_listeners( $event_name );
	}

	/**
	 * Determine if a given event has wildcard listeners.
	 *
	 * @param  string $event_name Event name.
	 */
	public function has_wildcard_listeners( string $event_name ): bool {
		return ! empty( $this->get_wildcard_listeners( $event_name ) );
	}

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string $subscriber
	 */
	public function subscribe( object|string $subscriber ): void {
		$subscriber = $this->resolve_subscriber( $subscriber );

		$subscriber->subscribe( $this );
	}

	/**
	 * Resolve the subscriber instance.
	 *
	 * @param  object|string $subscriber
	 * @return mixed
	 */
	protected function resolve_subscriber( object|string $subscriber ) {
		if ( is_string( $subscriber ) ) {
			return $this->container->make( $subscriber );
		}

		return $subscriber;
	}

	/**
	 * Fire an event and call the listeners.
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

		[ $event, $payload ] = $this->parse_event_and_payload( $event, $payload );

		if ( ! function_exists( 'apply_filters' ) ) {
			return null;
		}

		// Ensure there is a payload that is able to be passed to the filter.
		if ( empty( $payload ) ) {
			// @phpstan-ignore offsetAccess.nonOffsetAccessible
			$payload[] = ''; // Mirror the default behavior of do_action.
		}

		// @phpstan-ignore-next-line argument.type
		return apply_filters( $event, ...$payload ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
	}

	/**
	 * Parse the given event and payload and prepare them for dispatching.
	 *
	 * @param  string|object $event
	 * @param  mixed $payload
	 * @return array{0: string, 1: mixed}
	 */
	protected function parse_event_and_payload( string|object $event, mixed $payload ): array {
		if ( is_object( $event ) ) {
			[ $payload, $event ] = [ [ $event ], $event::class ];
		}

		return [ $event, Arr::wrap( $payload ) ];
	}

	/**
	 * Get all of the wildcard listeners for a given event name.
	 *
	 * @param  string $event_name
	 * @return array<string|callable>
	 */
	public function get_wildcard_listeners( string $event_name ): array {
		if ( isset( $this->wildcard_cache[ $event_name ] ) ) {
			return $this->wildcard_cache[ $event_name ];
		}

		$listeners = [];

		foreach ( $this->wildcard_listeners as $pattern => $wildcard_listeners ) {
			if ( Str::is( $pattern, $event_name ) ) {
				$listeners = array_merge( $listeners, $wildcard_listeners );
			}
		}

		$this->wildcard_cache[ $event_name ] = $listeners;

		return $listeners;
	}

	/**
	 * Add the listeners for the event's interfaces to the given array.
	 *
	 * @param  string $event_name
	 * @param  array<mixed>  $listeners
	 * @return array<mixed>
	 */
	protected function add_interface_listeners( string $event_name, array $listeners = [] ): array {
		foreach ( class_implements( $event_name ) ?: [] as $interface ) {
			if ( isset( $this->listeners[ $interface ] ) ) {
				foreach ( $this->listeners[ $interface ] as $names ) {
					$listeners = array_merge( $listeners, (array) $names );
				}
			}
		}

		return $listeners;
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  callable|string $listener
	 */
	public function make_listener( callable|string $listener ): Closure {
		if ( is_string( $listener ) ) {
			return $this->create_class_listener( $listener );
		}

		return fn ( ...$payload ) => $this->create_action_callback(
			$listener,
		)( ...array_values( $payload ) );
	}

	/**
	 * Create a class based listener using the IoC container.
	 *
	 * @param  string $listener
	 */
	public function create_class_listener( string $listener ): Closure {
		return function ( ...$payload ) use ( $listener ) {
			$callable = $this->create_action_callback(
				// @phpstan-ignore argument.type
				$this->create_class_callable( $listener ),
			);

			return $callable( ...array_values( $payload ) );
		};
	}

	/**
	 * Create the class based event callable.
	 *
	 * @param  string $listener
	 * @return array{0: object, 1: string|null}
	 */
	protected function create_class_callable( string $listener ): array {
		[ $class, $method ] = $this->parse_class_callable( $listener );

		// todo: add queued callback support.

		return [ $this->container->make( $class ), $method ];
	}

	/**
	 * Parse the class listener into class and method.
	 *
	 * @param  string $listener
	 * @return array{0: string, 1: string|null}
	 */
	protected function parse_class_callable( string $listener ): array {
		return Str::parse_callback( $listener, 'handle' );
	}

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param string|object        $event Event to remove.
	 * @param callable|string|null $listener Listener to remove.
	 * @param int                  $priority Priority of the listener.
	 */
	public function forget( string|object $event, string|callable|null $listener = null, int $priority = 10 ): void {
		if ( is_object( $event ) ) {
			$event = $event::class;
		}

		if ( str_contains( $event, '*' ) ) {
			$this->forget_wildcard( $event, $listener );

			return;
		}

		if ( null === $listener ) {
			remove_all_filters( $event, $priority );
		} else {
			remove_filter( $event, $listener, $priority );
		}
	}

	/**
	 * Remove a wildcard listener from the dispatcher.
	 *
	 * @param string $event Event to remove.
	 * @param string|callable|null $listener Listener to remove.
	 */
	public function forget_wildcard( string $event, string|callable|null $listener = null ): void {
		if ( empty( $this->wildcard_listeners[ $event ] ) ) {
			return;
		}

		$this->wildcard_cache = [];

		if ( null === $listener ) {
			unset( $this->wildcard_listeners[ $event ] );

			return;
		}

		$this->wildcard_listeners[ $event ] = array_filter(
			$this->wildcard_listeners[ $event ],
			fn ( callable|string $value ) => $value !== $listener,
		);

		if ( empty( $this->wildcard_listeners[ $event ] ) ) {
			unset( $this->wildcard_listeners[ $event ] );
		}
	}

	/**
	 * Setup a wildcard event listener.
	 *
	 * Registers a listener for the 'all' action which is fired for all hooks
	 * which we can then use to find the appropriate listeners. Wildcard events
	 * cannot have a priority.
	 *
	 * @param string          $event Event name to listen to with * wildcard.
	 * @param string|callable $listener Listener to register.
	 */
	protected function setup_wildcard_listener( string $event, string|callable $listener ): void {
		if ( function_exists( 'has_action' ) && ! has_action( 'all', [ $this, 'wildcard_listener_callback' ] ) ) {
			add_action( 'all', [ $this, 'wildcard_listener_callback' ] );
		}

		$this->wildcard_cache = [];

		$this->wildcard_listeners[ $event ][] = $listener;
	}

	/**
	 * Callback for the wildcard listener.
	 *
	 * Hooked to the 'all' action to catch all events.
	 *
	 * @param string $hook Hook being fired.
	 * @param mixed ...$args Arguments for the hook.
	 */
	public function wildcard_listener_callback( string $hook, mixed ...$args ): void {
		foreach ( $this->wildcard_listeners as $pattern => $listeners ) {
			if ( ! Str::is( $pattern, $hook ) ) {
				continue;
			}

			foreach ( $listeners as $listener ) {
				// @phpstan-ignore argument.type
				$callable = $this->create_action_callback( $listener );

				$callable( $hook, ...$args );
			}
		}
	}
}
