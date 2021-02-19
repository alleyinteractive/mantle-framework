<?php
/**
 * Job class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue;

/**
 * Abstract Queue Job
 *
 * To be extended by provider-specific queue job classes.
 */
abstract class Job {
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
}
