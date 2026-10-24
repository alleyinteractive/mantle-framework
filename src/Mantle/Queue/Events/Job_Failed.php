<?php
/**
 * Job_Failed class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;
use Throwable;

/**
 * Job Failed Event
 */
class Job_Failed {
	/**
	 * Constructor.
	 *
	 * @param Provider  $provider Queue provider.
	 * @param mixed     $job      Job object.
	 * @param Throwable $e        Exception.
	 */
	public function __construct( public Provider $provider, public mixed $job, public Throwable $e ) {}

	/**
	 * Get the ID for the job.
	 */
	public function get_id(): mixed {
		return $this->job->get_id();
	}
}
