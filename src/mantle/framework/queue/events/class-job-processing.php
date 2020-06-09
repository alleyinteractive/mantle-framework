<?php
/**
 * Job_Processing class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue\Events;

/**
 * Job Processing Event
 */
class Job_Processing {
	/**
	 * Job Data
	 *
	 * @var mixed
	 */
	public $job;

	/**
	 * Constructor.
	 *
	 * @param mixed $job Job object.
	 */
	public function __construct( $job ) {
		$this->job = $job;
	}

	/**
	 * Get the ID for the job.
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->job->get_id();
	}
}
