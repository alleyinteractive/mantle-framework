<?php
/**
 * Wp_Cron_Job class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue;

use Mantle\Framework\Contracts\Queue\Job as JobContract;

/**
 * WordPress Cron Queue Job
 */
class Wp_Cron_Job extends Job {
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
	 *
	 * @todo Add error handling for the queue item.
	 */
	public function fire() {
		if ( $this->job instanceof JobContract ) {
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
}
