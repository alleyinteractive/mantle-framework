<?php
/**
 * Call_Queued_Listener class file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Events;

use DateTimeInterface;
use Mantle\Container\Container;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Job;

/**
 * Queue job that runs a class-based event listener with the event payload.
 */
class Call_Queued_Listener implements Can_Queue, Job {
	/**
	 * The name of the queue the job should be sent to.
	 */
	public string $queue;

	/**
	 * The delay before the job will be run.
	 */
	public int|DateTimeInterface $delay;

	/**
	 * Constructor.
	 *
	 * @param class-string $class Listener class name.
	 * @param string       $method Listener method to call.
	 * @param array<mixed> $data Event payload passed to the listener.
	 */
	public function __construct(
		public string $class,
		public string $method,
		public array $data,
	) {}

	/**
	 * Resolve the listener from the container and call it with the payload.
	 */
	public function handle(): void {
		$container = Container::get_instance();
		$callback  = [ $container->make( $this->class ), $this->method ];
		$events    = $container->bound( 'events' ) ? $container->make( 'events' ) : null;

		if ( $events instanceof Dispatcher && is_callable( $callback ) ) {
			$events->make_listener( $callback )( ...array_values( $this->data ) );

			return;
		}

		$callback( ...array_values( $this->data ) ); // @phpstan-ignore callable.nonCallable
	}

	/**
	 * Get a display name for the job.
	 */
	public function get_id(): string {
		return $this->class . '@' . $this->method;
	}
}
