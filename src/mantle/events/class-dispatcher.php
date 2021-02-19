<?php
/**
 * Dispatcher class file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Events;

use Exception;
use Mantle\Container\Container;
use Mantle\Framework\Contracts\Events\Dispatcher as Dispatcher_Contract;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use ReflectionClass;

use function Mantle\Framework\Helpers\tap;

/**
 * Event Dispatcher
 */
class Dispatcher implements Dispatcher_Contract {
	use WordPress_Action;

	/**
	 * The IoC container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * The registered event listeners.
	 *
	 * @var array
	 */
	protected $listeners = [];

	/**
	 * The wildcard listeners.
	 *
	 * @var array
	 */
	protected $wildcards = [];

	/**
	 * The cached wildcard listeners.
	 *
	 * @var array
	 */
	protected $wildcards_cache = [];

	/**
	 * The queue resolver instance.
	 *
	 * @var callable
	 */
	protected $queue_resolver;

	/**
	 * Create a new event dispatcher instance.
	 *
	 * @param Container|null $container Container instance.
	 */
	public function __construct( Container $container = null ) {
		$this->container = $container ?: new Container();
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string|array    $events Events to listen to.
	 * @param  \Closure|string $listener Listener callback.
	 */
	public function listen( $events, $listener ) {
		foreach ( (array) $events as $event ) {
			if ( Str::contains( $event, '*' ) ) {
				$this->setup_wildcard_listener( $event, $listener );
			} else {
				$this->listeners[ $event ][] = $this->make_listener( $listener );
			}
		}
	}

	/**
	 * Setup a wildcard listener callback.
	 *
	 * @param  string          $event Event name.
	 * @param  \Closure|string $listener Event callback.
	 */
	protected function setup_wildcard_listener( $event, $listener ) {
		$this->wildcards[ $event ][] = $this->make_listener( $listener, true );

		$this->wildcards_cache = [];
	}

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string $event_name Event name.
	 * @return bool
	 */
	public function has_listeners( $event_name ): bool {
		return isset( $this->listeners[ $event_name ] ) ||
			isset( $this->wildcards[ $event_name ] ) ||
			$this->has_wildcard_listeners( $event_name );
	}

	/**
	 * Determine if the given event has any wildcard listeners.
	 *
	 * @param string $event_name Event name.
	 * @return bool
	 */
	public function has_wildcard_listeners( $event_name ): bool {
		foreach ( $this->wildcards as $key => $listeners ) {
			if ( Str::is( $key, $event_name ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register an event and payload to be fired later.
	 *
	 * @param string $event Event name.
	 * @param array  $payload Event payload.
	 */
	public function push( $event, $payload = [] ) {
		$this->listen(
			$event . '_pushed',
			function () use ( $event, $payload ) {
				$this->dispatch( $event, $payload );
			}
		);
	}

	/**
	 * Flush a set of pushed events.
	 *
	 * @param string $event Event name.
	 */
	public function flush( $event ) {
		$this->dispatch( $event . '_pushed' );
	}

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string $subscriber
	 * @return void
	 */
	public function subscribe( $subscriber ) {
		$subscriber = $this->resolve_subscriber( $subscriber );

		$subscriber->subscribe( $this );
	}

	/**
	 * Resolve the subscriber instance.
	 *
	 * @param  object|string $subscriber
	 * @return mixed
	 */
	protected function resolve_subscriber( $subscriber ) {
		if ( is_string( $subscriber ) ) {
			return $this->container->make( $subscriber );
		}

		return $subscriber;
	}

	/**
	 * Fire an event until the first non-null response is returned.
	 *
	 * @param  string|object $event Event name.
	 * @param  mixed         $payload Event payload.
	 * @return array|null
	 */
	public function until( $event, $payload = [] ) {
		return $this->dispatch( $event, $payload, true );
	}

	/**
	 * Fire an event and call the listeners.
	 *
	 * @param  string|object $event Event name.
	 * @param  mixed         $payload Event payload.
	 * @param  bool          $halt Flag to halt.
	 * @return array|null
	 */
	public function dispatch( $event, $payload = [], $halt = false ) {
		// When the given "event" is actually an object we will assume it is an event
		// object and use the class as the event name and this event itself as the
		// payload to the handler, which makes object based events quite simple.
		[ $event, $payload ] = $this->parse_event_and_payload(
			$event,
			$payload
		);

		if ( $this->should_broadcast( $payload ) && isset( $payload[0] ) ) {
			$this->broadcast_event( $payload[0] );
		}

		$responses = [];

		foreach ( $this->get_listeners( $event ) as $listener ) {
			$response = $listener( $event, $payload );

			// If a response is returned from the listener and event halting is enabled
			// we will just return this response, and not call the rest of the event
			// listeners. Otherwise we will add the response on the response list.
			if ( $halt && ! is_null( $response ) ) {
				return $response;
			}

			// If a boolean false is returned from a listener, we will stop propagating
			// the event to any further listeners down in the chain, else we keep on
			// looping through the listeners and firing every one in our sequence.
			if ( false === $response ) {
				break;
			}

			$responses[] = $response;
		}

		return $halt ? null : $responses;
	}

	/**
	 * Parse the given event and payload and prepare them for dispatching.
	 *
	 * @param  mixed $event
	 * @param  mixed $payload
	 * @return array
	 */
	protected function parse_event_and_payload( $event, $payload ) {
		if ( is_object( $event ) ) {
			[ $payload, $event ] = [ [ $event ], get_class( $event ) ];
		}

		return [ $event, Arr::wrap( $payload ) ];
	}

	/**
	 * Determine if the payload has a broadcastable event.
	 *
	 * @todo Add support for broadcasted events.
	 *
	 * @param  array $payload
	 * @return bool
	 */
	protected function should_broadcast( array $payload ): bool {
		return false;
	}

	/**
	 * Broadcast the given event class.
	 *
	 * @param mixed Event3 class.
	 * @return void
	 */
	protected function broadcast_event( $event ) {
		$this->container->make( BroadcastFactory::class )->queue( $event );
	}

	/**
	 * Get all of the listeners for a given event name.
	 *
	 * @param  string $event_name
	 * @return array
	 */
	public function get_listeners( $event_name ) {
		$listeners = $this->listeners[ $event_name ] ?? [];

		$listeners = array_merge(
			$listeners,
			$this->wildcards_cache[ $event_name ] ?? $this->get_wildcard_listeners( $event_name )
		);

		return class_exists( $event_name, false )
			? $this->add_interface_listeners( $event_name, $listeners )
			: $listeners;
	}

	/**
	 * Get the wildcard listeners for the event.
	 *
	 * @param  string $event_name
	 * @return array
	 */
	protected function get_wildcard_listeners( $event_name ) {
		$wildcards = [];

		foreach ( $this->wildcards as $key => $listeners ) {
			if ( Str::is( $key, $event_name ) ) {
				$wildcards = array_merge( $wildcards, $listeners );
			}
		}

		$this->wildcards_cache[ $event_name ] = $wildcards;

		return $wildcards;
	}

	/**
	 * Add the listeners for the event's interfaces to the given array.
	 *
	 * @param  string $event_name
	 * @param  array  $listeners
	 * @return array
	 */
	protected function add_interface_listeners( $event_name, array $listeners = [] ) {
		foreach ( class_implements( $event_name ) as $interface ) {
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
	 * @param  \Closure|string $listener
	 * @param  bool            $wildcard
	 * @return \Closure
	 */
	public function make_listener( $listener, $wildcard = false ) {
		if ( is_string( $listener ) ) {
			return $this->create_class_listener( $listener, $wildcard );
		}

		return function ( $event, $payload ) use ( $listener, $wildcard ) {
			if ( $wildcard ) {
				return $listener( $event, $payload );
			}

			return $listener( ...array_values( $payload ) );
		};
	}

	/**
	 * Create a class based listener using the IoC container.
	 *
	 * @param  string $listener
	 * @param  bool   $wildcard
	 * @return \Closure
	 */
	public function create_class_listener( $listener, $wildcard = false ) {
		return function ( $event, $payload ) use ( $listener, $wildcard ) {
			if ( $wildcard ) {
				return call_user_func( $this->create_class_callable( $listener ), $event, $payload );
			}

			return call_user_func_array(
				$this->create_class_callable( $listener ),
				$payload
			);
		};
	}

	/**
	 * Create the class based event callable.
	 *
	 * @param  string $listener
	 * @return callable
	 */
	protected function create_class_callable( $listener ) {
		[ $class, $method ] = $this->parse_class_callable( $listener );

		if ( $this->handler_should_be_queued( $class ) ) {
			return $this->create_queued_handler_callable( $class, $method );
		}

		return [ $this->container->make( $class ), $method ];
	}

	/**
	 * Parse the class listener into class and method.
	 *
	 * @param  string $listener
	 * @return array
	 */
	protected function parse_class_callable( $listener ) {
		return Str::parse_callback( $listener, 'handle' );
	}

	/**
	 * Determine if the event handler class should be queued.
	 *
	 * @param  string $class
	 * @return bool
	 */
	protected function handler_should_be_queued( $class ) {
		try {
			return ( new ReflectionClass( $class ) )->implementsInterface(
				Should_Queue::class
			);
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Create a callable for putting an event handler on the queue.
	 *
	 * @param  string $class
	 * @param  string $method
	 * @return \Closure
	 */
	protected function create_queued_handler_callable( $class, $method ) {
		return function () use ( $class, $method ) {
			$arguments = array_map(
				function ( $a ) {
					return is_object( $a ) ? clone $a : $a;
				},
				func_get_args()
			);

			if ( $this->handler_wants_to_be_queued( $class, $arguments ) ) {
				$this->queue_handler( $class, $method, $arguments );
			}
		};
	}

	/**
	 * Determine if the event handler wants to be queued.
	 *
	 * @param  string $class
	 * @param  array  $arguments
	 * @return bool
	 */
	protected function handler_wants_to_be_queued( $class, $arguments ) {
		$instance = $this->container->make( $class );

		if ( method_exists( $instance, 'shouldQueue' ) ) {
			return $instance->shouldQueue( $arguments[0] );
		}

		return true;
	}

	/**
	 * Queue the handler class.
	 *
	 * @param  string $class
	 * @param  string $method
	 * @param  array  $arguments
	 * @return void
	 */
	protected function queue_handler( $class, $method, $arguments ) {
		[ $listener, $job ] = $this->create_listener_and_job( $class, $method, $arguments );

		$connection = $this->resolve_queue()->connection(
			$listener->connection ?? null
		);

		$queue = $listener->queue ?? null;

		isset( $listener->delay )
			? $connection->laterOn( $queue, $listener->delay, $job )
			: $connection->pushOn( $queue, $job );
	}

	/**
	 * Create the listener and job for a queued listener.
	 *
	 * @param  string $class
	 * @param  string $method
	 * @param  array  $arguments
	 * @return array
	 */
	protected function create_listener_and_job( $class, $method, $arguments ) {
		$listener = ( new ReflectionClass( $class ) )->newInstanceWithoutConstructor();

		return [
			$listener,
			$this->propagate_listener_options(
				$listener,
				new CallQueuedListener( $class, $method, $arguments )
			),
		];
	}

	/**
	 * Propagate listener options to the job.
	 *
	 * @param  mixed $listener
	 * @param  mixed $job
	 * @return mixed
	 */
	protected function propagate_listener_options( $listener, $job ) {
		return tap(
			$job,
			function ( $job ) use ( $listener ) {
				$job->tries       = $listener->tries ?? null;
				$job->retry_after = method_exists( $listener, 'retry_after' )
					? $listener->retry_after() : ( $listener->retry_after ?? null );
				$job->timeout     = $listener->timeout ?? null;
				$job->timeout_at  = method_exists( $listener, 'retry_until' )
					? $listener->retry_until() : null;
			}
		);
	}

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param  string $event
	 * @return void
	 */
	public function forget( $event ) {
		if ( Str::contains( $event, '*' ) ) {
			unset( $this->wildcards[ $event ] );
		} else {
			unset( $this->listeners[ $event ] );
		}

		foreach ( $this->wildcards_cache as $key => $listeners ) {
			if ( Str::is( $event, $key ) ) {
				unset( $this->wildcards_cache[ $key ] );
			}
		}
	}

	/**
	 * Forget all of the pushed listeners.
	 *
	 * @return void
	 */
	public function forget_pushed() {
		foreach ( $this->listeners as $key => $value ) {
			if ( Str::ends_with( $key, '_pushed' ) ) {
				$this->forget( $key );
			}
		}
	}

	/**
	 * Get the queue implementation from the resolver.
	 *
	 * @return Queue
	 */
	protected function resolve_queue() {
		return call_user_func( $this->queue_resolver );
	}

	/**
	 * Set the queue resolver implementation.
	 *
	 * @param  callable $resolver
	 * @return $this
	 */
	public function set_queue_resolver( callable $resolver ) {
		$this->queue_resolver = $resolver;

		return $this;
	}
}
