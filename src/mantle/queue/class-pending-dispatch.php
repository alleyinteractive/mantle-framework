<?php
/**
 * Pending_Dispatch class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use DateTimeInterface;
use Mantle\Container\Container;
use Mantle\Contracts\Queue\Dispatcher;
use Mantle\Contracts\Queue\Job;
use RuntimeException;

/**
 * Allow jobs to be added to the queue with ease.
 */
class Pending_Dispatch {
	/**
	 * Constructor.
	 *
	 * @param Job|Closure_Job $job Job instance.
	 */
	public function __construct( protected Job|Closure_Job $job ) {}

	/**
	 * Add a dispatch to a specific queue.
	 *
	 * @throws RuntimeException If the job does not support queueing.
	 *
	 * @param string $queue Queue to add to.
	 */
	public function on_queue( string $queue ): Pending_Dispatch {
		if ( ! method_exists( $this->job, 'on_queue' ) ) {
			throw new RuntimeException( 'Job does not support queueing.' );
		}

		$this->job->on_queue( $queue );

		return $this;
	}

	/**
	 * Set the delay before the job will be run.
	 *
	 * @throws RuntimeException If the job does not support queueing.
	 *
	 * @param DateTimeInterface|int $delay Delay in seconds or DateTime instance.
	 */
	public function delay( DateTimeInterface|int $delay ): Pending_Dispatch {
		if ( ! method_exists( $this->job, 'delay' ) ) {
			throw new RuntimeException( $this->job::class . ' does not support delayed queueing.' );
		}

		$this->job->delay( $delay );

		return $this;
	}

	/**
	 * Handle the job and send it to the queue.
	 */
	public function __destruct() {
		if ( ! isset( $this->job ) ) {
			return;
		}

		// Allow the queue package to be run independent of the application.
		if ( ! class_exists( \Mantle\Application\Application::class ) ) {
			Container::get_instance()->make( Dispatcher::class )->dispatch( $this->job );
		} else {
			app( Dispatcher::class )->dispatch( $this->job );
		}
	}
}
