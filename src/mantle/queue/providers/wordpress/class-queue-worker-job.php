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
	 * @param mixed $job Job data.
	 * @param int   $queue_post_id Queue post ID.
	 */
	public function __construct( protected mixed $job, protected ?int $queue_post_id = null ) {}

	/**
	 * Fire the job.
	 */
	public function fire(): void {
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
		return $this->queue_post_id;
	}

	/**
	 * Get the queue job ID.
	 *
	 * @return mixed
	 */
	public function get_id(): mixed {
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
	public function failed( Throwable $e ): void {
		$this->failed = true;

		if ( $this->queue_post_id ) {
			update_post_meta( $this->queue_post_id, '_mantle_queue_error', $e->getMessage() );

			wp_update_post(
				[
					'ID'          => $this->queue_post_id,
					'post_status' => Post_Status::FAILED->value,
				]
			);
		}
	}

	/**
	 * Delete the job from the queue.
	 */
	public function delete(): void {
		$post_id = $this->get_post_id();

		if ( $post_id && wp_delete_post( $post_id, true ) ) {
			$this->queue_post_id = null;
		}
	}
}
