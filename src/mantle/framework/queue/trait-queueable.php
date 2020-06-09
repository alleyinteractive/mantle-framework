<?php
/**
 * Queueable trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue;

/**
 * Queueable trait for queue jobs.
 *
 * Provides methods to interact with async queue jobs.
 */
trait Queueable {
	/**
	 * The delay before the job will be run.
	 *
	 * @var int
	 */
	protected $delay;

	/**
	 * The name of the queue for the job.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * Add a dispatch to a specific queue.
	 *
	 * @param string $queue Queue to add to.
	 * @return static
	 */
	public function on_queue( string $queue ) {
		$this->queue = $queue;
		return $this;
	}

	/**
	 * Set the delay before the job will be run.
	 *
	 * @param int $delay Delay in seconds.
	 * @return static
	 */
	public function delay( int $delay ) {
		$this->delay = $delay;
		return $this;
	}
}
