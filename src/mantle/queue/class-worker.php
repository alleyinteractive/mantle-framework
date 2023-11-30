<?php
/**
 * Worker class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Mantle\Contracts\Events\Dispatcher;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Queue\Events\Job_Failed;
use Mantle\Queue\Events\Job_Processed;
use Mantle\Queue\Events\Job_Processing;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Events\Run_Start;
use Throwable;

/**
 * Queue Worker
 */
class Worker {
	/**
	 * Constructor.
	 *
	 * @param Queue_Manager $manager Manager instance.
	 * @param Dispatcher    $events Events dispatcher.
	 */
	public function __construct(
		protected Queue_Manager $manager,
		protected Dispatcher $events,
	) {
	}

	/**
	 * Run a batch of queue items.
	 *
	 * @param int    $size Size of the batch to run.
	 * @param string $queue Queue name.
	 */
	public function run( int $size, string $queue = null ): void {
		$queue  ??= 'default';
		$provider = $this->manager->get_provider();
		$jobs     = $provider->pop( $queue, $size );

		$this->events->dispatch( new Run_Start( $provider, $queue, $jobs ) );

		$jobs->each( [ $this, 'run_single' ] );

		$this->events->dispatch( new Run_Complete( $provider, $queue, $jobs ) );
	}

	/**
	 * Run a single queue job.
	 *
	 * @param Queue_Worker_Job $job Job to run.
	 * @return void
	 */
	public function run_single( Queue_Worker_Job $job ): void {
		$provider = $this->manager->get_provider();

		$this->events->dispatch( new Job_Processing( $provider, $job ) );

		try {
			$job->fire();

			$this->events->dispatch( new Job_Processed( $provider, $job ) );
		} catch ( Throwable $e ) {
			$job->failed( $e );

			$this->events->dispatch( new Job_Failed( $provider, $job, $e ) );
		} finally {
			if ( ! $job->has_failed() ) {
				$job->completed();
			} elseif ( $job->can_retry() ) {
				$job->retry( $job->get_retry_backoff() );
			}
		}
	}
}
