<?php
/**
 * Wp_Cron_Job class file.
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
	 * Raw job callback.
	 *
	 * @var mixed
	 */
	protected $job;

	/**
	 * Queue post ID.
	 *
	 * @var int
	 */
	protected $queue_post_id;

	/**
	 * Flag if the job failed.
	 *
	 * @var bool
	 */
	public bool $failed = false;

	/**
	 * Constructor.
	 *
	 * @param mixed $job Job data.
	 * @param int   $queue_post_id Queue post ID.
	 */
	public function __construct( $job, int $queue_post_id ) {
		$this->job           = $job;
		$this->queue_post_id = $queue_post_id;
	}

	/**
	 * Fire the job.
	 */
	public function fire() {
		// Check if the job has a method called 'handle'.
		if ( $this->job instanceof JobContract || method_exists( $this->job, 'handle' ) ) {
			$this->job->handle();
		} elseif ( is_callable( $this->job ) ) {
			$callback = $this->job;

			$callback();
		}
	}

	/**
	 * Get the queue post ID.
	 *
	 * @return int|null
	 */
	public function get_post_id(): ?int {
		return $this->queue_post_id ?? null;
	}

	/**
	 * Get the queue job ID.
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->get_post_id();
	}

	/**
	 * Handle a failed queue job.
	 *
	 * @todo Add retrying for queued jobs.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return void
	 */
	public function failed( Throwable $e ) {
		$this->failed = true;

		if ( $this->queue_post_id ) {
			update_post_meta( $this->queue_post_id, '_mantle_queue_error', $e->getMessage() );
			wp_update_post(
				[
					'ID'          => $this->queue_post_id,
					'post_status' => 'failed',
				]
			);
		}
	}

	/**
	 * Delete the job from the queue.
	 */
	public function delete() {
		$post_id = $this->get_post_id();

		if ( $post_id && wp_delete_post( $post_id, true ) ) {
			$this->queue_post_id = null;
		}
	}
}
