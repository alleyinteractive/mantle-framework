<?php
/**
 * Job_Processed class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;

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
	 * Constructor.
	 *
	 * @param Provider $provider Queue provider.
	 * @param mixed    $job Job object.
	 */
	public function __construct( Provider $provider, /**
	 * Job Data
	 */
 public mixed $job ) {
		$this->provider = $provider;
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
