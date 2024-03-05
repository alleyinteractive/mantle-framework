<?php
/**
 * Job_Processing class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;

/**
 * Job Processing Event
 */
class Job_Processing {
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
