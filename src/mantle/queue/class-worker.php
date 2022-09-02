<?php
/**
 * Worker class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Closure;
use Mantle\Contracts\Events\Dispatcher;
use Mantle\Contracts\Queue\Provider;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Queue\Events\Job_Failed;
use Mantle\Queue\Events\Job_Processed;
use Mantle\Queue\Events\Job_Processing;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Events\Run_Start;
use RuntimeException;
use Throwable;

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
	public function __construct(
		Queue_Manager $manager,
		Dispatcher $events,
	) {
		$this->manager = $manager;
		$this->events  = $events;
	}

	/**
	 * Run a batch of queue items.
	 *
	 * @todo Add failed job re-running and retrying.
	 *
	 * @param int    $size Size of the batch to run.
	 * @param string $queue Queue name.
	 */
	public function run( int $size, string $queue = null ) {
		$provider = $this->manager->get_provider();
		$jobs     = $provider->pop( $queue, $size );

		$this->events->dispatch( new Run_Start( $provider, $queue, $jobs ) );

		$jobs->each(
			function( $job ) use ( $provider ) {
				$this->events->dispatch( new Job_Processing( $provider, $job ) );

				try {
					if ( $job instanceof Closure || is_callable( $job ) ) {
						$job();
					} else {
						$job->fire();
					}

					$this->events->dispatch( new Job_Processed( $provider, $job ) );
				} catch ( Throwable $e ) {
					$this->handle_job_exception( $provider, $job, $e );

					$this->events->dispatch( new Job_Failed( $provider, $job, $e ) );
				} finally {
					if ( ! $job instanceof Closure && ! $job->has_failed() ) {
						$job->delete();
					}
				}
			}
		);

		$this->events->dispatch( new Run_Complete( $provider, $queue, $jobs ) );
	}

	/**
	 * Handle job exceptions.
	 *
	 * @todo Add add job retrying.
	 *
	 * @param Provider  $provider Queue provider.
	 * @param mixed     $job      Queue job.
	 * @param Throwable $e       Exception thrown.
	 * @return void
	 */
	protected function handle_job_exception( Provider $provider, $job, Throwable $e ) {
		$job->failed( $e );
	}
}
