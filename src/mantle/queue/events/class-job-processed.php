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
	 * Constructor.
	 *
	 * @param Provider $provider Queue provider.
	 * @param mixed    $job Job object.
	 */
	public function __construct( public Provider $provider, public mixed $job ) {}

	/**
	 * Get the ID for the job.
	 */
	public function get_id(): mixed {
		return $this->job?->get_id();
	}
}
