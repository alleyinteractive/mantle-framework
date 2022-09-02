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
	abstract public function fire();

	/**
	 * Get the queue job ID.
	 *
	 * @return mixed
	 */
	abstract public function get_id();

	/**
	 * Handle a failed job.
	 *
	 * @param Throwable $e
	 * @return void
	 */
	abstract public function failed( Throwable $e );

	/**
	 * Delete a job from the queue.
	 *
	 * @return void
	 */
	abstract public function delete();

	/**
	 * Check if the job has failed.
	 *
	 * @return bool
	 */
	public function has_failed(): bool {
		return $this->failed;
	}
}
