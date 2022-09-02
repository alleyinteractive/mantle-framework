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
	 * Exception
	 *
	 * @var Throwable
	 */
	public $exception;

	/**
	 * Constructor.
	 *
	 * @param Provider  $provider Queue provider.
	 * @param mixed     $job      Job object.
	 * @param Throwable $e        Exception.
	 */
	public function __construct( Provider $provider, $job, Throwable $e ) {
		$this->provider  = $provider;
		$this->job       = $job;
		$this->exception = $e;
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
