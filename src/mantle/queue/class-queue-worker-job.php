<?php
/**
 * Job class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Throwable;

/**
 * Abstract Queue Worker Job
 *
 * Base class for provider-specific queue worker job classes.
 */
abstract class Queue_Worker_Job {
	/**
	 * Flag if the job failed.
	 *
	 * @var bool
	 */
	public bool $failed = false;

	/**
	 * Fire the queue job.
	 */
	abstract public function fire(): void;

	/**
	 * Get the queue job ID.
	 *
	 * @return mixed
	 */
	abstract public function get_id(): mixed;

	/**
	 * Retrieve the stored job.
	 *
	 * @return mixed
	 */
	abstract public function get_job(): mixed;

	/**
	 * Handle a failed job.
	 *
	 * @param Throwable $e
	 * @return void
	 */
	abstract public function failed( Throwable $e ): void;

	/**
	 * Handle a completed job.
	 *
	 * @return void
	 */
	abstract public function completed(): void;

	/**
	 * Retry a job with a specified delay.
	 *
	 * @param int $delay Delay in seconds.
	 */
	abstract public function retry( int $delay = 0 ): void;

	/**
	 * Delete a job from the queue.
	 *
	 * @return void
	 */
	abstract public function delete(): void;

	/**
	 * Check if the job has failed.
	 *
	 * @return bool
	 */
	public function has_failed(): bool {
		return $this->failed;
	}

	/**
	 * Check if the job can be retried.
	 *
	 * @return bool
	 */
	public function can_retry(): bool {
		return $this->has_failed() && ( $this->get_job()->retry ?? false );
	}

	/**
	 * Retrieve the retry backoff.
	 *
	 * @return int The retry backoff in seconds.
	 */
	public function get_retry_backoff(): int {
		return $this->get_job()->retry_backoff ?? 0;
	}
}
