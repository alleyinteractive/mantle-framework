<?php
/**
 * Worker class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue;

use Mantle\Framework\Contracts\Events\Dispatcher;
use Mantle\Framework\Contracts\Queue\Queue_Manager;
use Mantle\Framework\Queue\Events\Job_Processed;
use Mantle\Framework\Queue\Events\Job_Processing;
use Mantle\Framework\Queue\Events\Run_Complete;
use Mantle\Framework\Queue\Events\Run_Start;

/**
 * Queue Worker
 */
class Worker {
	/**
	 * Queue Manager
	 *
	 * @var Queue_Manager
	 */
	protected $manager;

	/**
	 * Events Dispatcher.
	 *
	 * @var Dispatcher
	 */
	protected $events;

	/**
	 * Constructor.
	 *
	 * @param Queue_Manager $manager Manager instance.
	 * @param Dispatcher    $events Events dispatcher.
	 */
	public function __construct( Queue_Manager $manager, Dispatcher $events ) {
		$this->manager = $manager;
		$this->events  = $events;
	}

	/**
	 * Run a batch of queue items.
	 *
	 * @todo Add better error handling, failed job re-running.
	 *
	 * @param int    $size Size of the batch to run.
	 * @param string $queue Queue name.
	 */
	public function run_batch( int $size, string $queue = null ) {
		$provider = $this->manager->get_provider();
		$jobs     = $provider->pop( $queue, $size );

		$this->events->dispatch( new Run_Start( $queue, $jobs ) );

		foreach ( $jobs as $job ) {
			$this->events->dispatch( new Job_Processing( $job ) );

			$job->handle();

			$this->events->dispatch( new Job_Processed( $job ) );
		}

		$this->events->dispatch( new Run_Complete( $queue, $jobs ) );
	}
}
