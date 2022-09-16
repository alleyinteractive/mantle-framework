<?php
/**
 * Dispatcher class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Closure;
use Mantle\Contracts\Container;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Queue_Manager;

/**
 * Queue Dispatcher
 *
 * Executes jobs from the queue.
 */
class Dispatcher {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Dispatch the job to the queue.
	 *
	 * @param mixed $job Job instance.
	 * @return mixed
	 */
	public function dispatch( $job ) {
		if ( ! $this->should_command_be_queued( $job ) ) {
			return $this->dispatch_now( $job );
		}

		$manager = $this->container->make( Queue_Manager::class );

		// Send the job to the queue.
		$manager->get_provider()->push( $job );
	}

	/**
	 * Dispatch a job in the current process.
	 *
	 * @param mixed $job Job instance.
	 * @return mixed
	 */
	public function dispatch_now( $job ) {
		return $this->container->call( [ $job, 'handle' ] );
	}

	/**
	 * Check if the command should be queued.
	 *
	 * @param mixed $job Job instance.
	 * @return bool
	 */
	protected function should_command_be_queued( $job ): bool {
		return $job instanceof Can_Queue;
	}
}
