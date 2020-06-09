<?php
/**
 * Job_Processed class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue\Events;

/**
 * Job Processed Event
 */
class Job_Processed {
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
}
