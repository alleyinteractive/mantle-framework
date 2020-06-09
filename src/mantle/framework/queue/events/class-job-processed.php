<?php
/**
 * Job_Processed class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue\Events;

use Mantle\Framework\Contracts\Queue\Provider;

/**
 * Job Processed Event
 */
class Job_Processed {
	/**
	 * Queue provider.
	 *
	 * @var mixed
	 */
	public $provider;

	/**
	 * Job Data
	 *
	 * @var mixed
	 */
	public $job;

	/**
	 * Constructor.
	 *
	 * @param Provider $provider Queue provider.
	 * @param mixed $job Job object.
	 */
	public function __construct( Provider $provider, $job ) {
		$this->provider = $provider;
		$this->job      = $job;
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
