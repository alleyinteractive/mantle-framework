<?php
/**
 * Pending_Dispatch class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Container\Container;
use Mantle\Contracts\Queue\Dispatcher;
use Mantle\Contracts\Queue\Job;

/**
 * Allow jobs to be added to the queue with ease.
 */
class Pending_Dispatch {
	/**
	 * Job instance.
	 *
	 * @var Job
	 */
	protected $job;

	/**
	 * Constructor.
	 *
	 * @param Job|Closure_Job $job Job instance.
	 */
	public function __construct( $job ) {
		$this->job = $job;
	}

	/**
	 * Add a dispatch to a specific queue.
	 *
	 * @param string $queue Queue to add to.
	 * @return static
	 */
	public function on_queue( string $queue ): Pending_Dispatch {
		$this->job->on_queue( $queue );

		return $this;
	}

	/**
	 * Set the delay before the job will be run.
	 *
	 * @param int $delay Delay in seconds.
	 * @return static
	 */
	public function delay( int $delay ): Pending_Dispatch {
		$this->job->delay( $delay );

		return $this;
	}

	/**
	 * Handle the job and send it to the queue.
	 */
	public function __destruct() {
		if ( ! $this->job ) {
			return;
		}

		// Allow the queue package to be run independent of the application.
		if ( ! class_exists( \Mantle\Framework\Application::class ) ) {
			Container::getInstance()->make( Dispatcher::class )->dispatch( $this->job );
		} else {
			app( Dispatcher::class )->dispatch( $this->job );
		}
	}
}
