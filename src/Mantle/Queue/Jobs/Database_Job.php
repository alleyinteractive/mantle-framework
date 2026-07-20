<?php
/**
 * Queue_Job class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Jobs;

use Mantle\Application\Application;
use Mantle\Contracts\Queue\Job as JobContract;
use Mantle\Contracts\Queue\Queue_Manager;
use Mantle\Queue\Closure_Job;
use Mantle\Queue\Events\Job_Queued;
use Mantle\Queue\Database_Event;
use Throwable;

/**
 * WordPress Cron Queue Job
 *
 * Class to perform the actual queue job from the data stored in the queue
 * record from the database.
 */
class Database_Job extends Job {

	/**
	 * Flag if the job failed.
	 */
	public bool $failed = false;

	/**
	 * Constructor.
	 *
	 * @param Database_Job_Record $model The queue record.
	 */
	public function __construct( protected Database_Job_Record $model ) {}

	/**
	 * Fire the job.
	 */
	public function fire(): void {
		$this->model->log_event( Database_Event::STARTING );

		$job = $this->get_job();

		// Check if the job has a method called 'handle'.
		if ( $job instanceof JobContract || ( is_object( $job ) && method_exists( $job, 'handle' ) ) ) {
			$job->handle();
		} elseif ( is_callable( $job ) ) {
			$job();
		}

		$this->model->log_event( Database_Event::FINISHED );
	}

	/**
	 * Get the queue job ID.
	 */
	public function get_id(): string {
		$job = $this->get_job();

		return (string) match ( true ) {
			$job instanceof Closure_Job => $job->get_id(),
			is_object( $job ) => $job::class,
			default => $this->model->id,
		};
	}

	/**
	 * Handle a failed queue job.
	 *
	 * @param Throwable $e Exception thrown.
	 */
	public function failed( Throwable $e ): void {
		$this->failed = true;

		$job = $this->get_job();

		$max_attempts = $job->tries ?? 1;

		$this->model->attempts += 1;

		$this->model->last_attempt_gmt = now()->toDateTimeString();

		// If the job has exceeded the maximum number of attempts, set the status to
		// failed. Otherwise, it will be retried.
		if ( $this->model->attempts >= $max_attempts ) {
			$this->model->status = Status::FAILED->value;
		}

		$this->model->save();

		$this->model->log_event( Database_Event::FAILED, [
			'exception' => $e::class,
			'message'   => $e->getMessage(),
			'trace'     => explode( "\n", $e->getTraceAsString() ),
		] );

		$job = $this->get_job();

		if ( is_object( $job ) && method_exists( $job, 'failed' ) ) {
			$job->failed( $e );
		}
	}

	/**
	 * Handle a completed job.
	 */
	public function completed(): void {
		$this->model->save( [
			'status' => Status::COMPLETED->value,
		] );

		$job = $this->get_job();

		if ( is_object( $job ) && method_exists( $job, 'completed' ) ) {
			$job->completed();
		}
	}

	/**
	 * Delete the job from the queue.
	 */
	public function delete(): void {
		$this->model->delete( true );
	}

	/**
	 * Check if the job can be retried.
	 */
	public function can_retry(): bool {
		$max_attempts = $this->get_job()->tries ?? 1;

		return $this->has_failed() && $max_attempts && $this->model->attempts < $max_attempts;
	}

	/**
	 * Retry a job with a specified delay.
	 *
	 * @param int $delay Delay in seconds.
	 */
	public function retry( int $delay = 0 ): void {
		$this->model->log_event( Database_Event::RETRYING, [ 'delay' => $delay ] );

		$this->model->save( [
			'status'           => Status::PENDING->value,
			'available_at_gmt' => now()->addSeconds( $delay )->toDateTimeString(),
		] );

		$app = Application::get_instance();

		// Dispatch the job queued event.
		$app['events']->dispatch(
			new Job_Queued(
				$app->make( Queue_Manager::class )->get_provider(),
				$this->get_job(),
			),
		);
	}

	/**
	 * Retrieve the stored job.
	 */
	public function get_job(): mixed {
		return maybe_unserialize( $this->model->action );
	}
}
