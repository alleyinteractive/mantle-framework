<?php
/**
 * Queueable trait file.
 *
 * phpcs:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use DateTimeInterface;

/**
 * Queueable trait for queue jobs.
 *
 * Provides methods to interact with async queue jobs.
 */
trait Queueable {
	/**
	 * The delay before the job will be run.
	 */
	public int|DateTimeInterface $delay;

	/**
	 * The name of the queue for the job.
	 */
	public string $queue;

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
	 * @param DateTimeInterface|int $delay Delay in seconds or DateTime instance.
	 * @return static
	 */
	public function delay( DateTimeInterface|int $delay ) {
		$this->delay = $delay;

		return $this;
	}
}
