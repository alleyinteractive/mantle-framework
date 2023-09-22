<?php
/**
 * Queue_Worker_Job class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

use Mantle\Contracts\Queue\Job as JobContract;
use Throwable;

/**
 * WordPress Cron Queue Job
 */
class Queue_Worker_Job extends \Mantle\Queue\Queue_Worker_Job {

	/**
	 * Flag if the job failed.
	 *
	 * @var bool
	 */
	public bool $failed = false;

	/**
	 * Constructor.
	 *
	 * @param Queue_Job $model The job model used for storage.
	 */
	public function __construct( protected Queue_Job $model ) {}

	/**
	 * Fire the job.
	 */
	public function fire(): void {
		// Refresh the model once more to ensure we have the latest data.
		$this->model->refresh();

		$this->model->log( Event::STARTING );

		// Mark the job as "running".
		$this->model->save(
			[
				'post_status' => Post_Status::RUNNING->value,
			]
		);

		$job = $this->get_job();

		// Set the lock end time.
		$this->model->set_lock_until( time() + ( $job->timeout ?? 600 ) );

		// Check if the job has a method called 'handle'.
		if ( $job instanceof JobContract || method_exists( $job, 'handle' ) ) {
			$job->handle();
		} elseif ( is_callable( $job ) ) {
			$job();
		}

		$this->model->log( Event::FINISHED );
	}

	/**
	 * Get the queue job ID.
	 *
	 * @return mixed
	 */
	public function get_id(): mixed {
		return $this->model->id();
	}

	/**
	 * Handle a failed queue job.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return void
	 */
	public function failed( Throwable $e ): void {
		$this->failed = true;

		$this->model->log(
			Event::FAILED,
			[
				'exception' => $e::class,
				'message'   => $e->getMessage(),
			],
		);

		$this->model->save(
			[
				'meta'        => [
					Meta_Key::FAILURE->value    => $e->getMessage(),
					Meta_Key::LOCK_UNTIL->value => '',
				],
				'post_status' => Post_Status::FAILED->value,
			]
		);

		$job = $this->get_job();

		if ( method_exists( $job, 'failed' ) ) {
			$job->failed( $e );
		}
	}

	/**
	 * Delete the job from the queue.
	 */
	public function delete(): void {
		$this->model->delete( true );
	}

	/**
	 * Retry a job with a specified delay.
	 *
	 * @param int $delay Delay in seconds.
	 */
	public function retry( int $delay = 0 ): void {
		$this->model->log( Event::RETRYING, [ 'delay' => $delay ] );

		$this->model->save(
			[
				'post_date'   => now()->addSeconds( $delay )->toDateTimeString(),
				'post_status' => Post_Status::PENDING->value,
			]
		);
	}

	/**
	 * Retrieve the stored job.
	 *
	 * @return mixed
	 */
	public function get_job(): mixed {
		return $this->model->get_meta( Meta_Key::JOB->value, true );
	}
}
