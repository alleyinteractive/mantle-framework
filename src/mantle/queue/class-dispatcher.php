<?php
/**
 * Dispatcher class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Contracts\Application;
use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Queue\Events\Job_Queued;

/**
 * Queue Dispatcher
 *
 * Executes jobs from the queue.
 */
class Dispatcher {
	/**
	 * Constructor.
	 *
	 * @param Application $container Container instance.
	 */
	public function __construct( protected Application $container ) {}

	/**
	 * Dispatch the job to the queue.
	 *
	 * @param mixed $job Job instance.
	 */
	public function dispatch( mixed $job ): void {
		if ( ! $this->should_command_be_queued( $job ) ) {
			$this->dispatch_now( $job );

			return;
		}

		/**
		 * Provider instance.
		 *
		 * @var \Mantle\Contracts\Queue\Provider
		 */
		$provider = $this->container->make( Queue_Manager::class )->get_provider();

		// Send the job to the queue.
		$provider->push( $job );

		// Dispatch the job queued event.
		$this->container['events']->dispatch(
			new Job_Queued( $provider, $job ),
		);
	}

	/**
	 * Dispatch the job after sending the given response.
	 *
	 * @param mixed $job Job instance.
	 */
	public function dispatch_after_response( mixed $job ): void {
		$this->container->terminating( fn () => $this->dispatch_now( $job ) );
	}

	/**
	 * Dispatch a job in the current process.
	 *
	 * @param mixed $job Job instance.
	 */
	public function dispatch_now( mixed $job ): void {
		$this->container->call( [ $job, 'handle' ] );
	}

	/**
	 * Check if the command should be queued.
	 *
	 * @param mixed $job Job instance.
	 */
	protected function should_command_be_queued( $job ): bool {
		return $job instanceof Can_Queue;
	}
}
