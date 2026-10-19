<?php
/**
 * Job_Queued class file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;

/**
 * Event for when a job is queued.
 */
class Job_Queued {
	/**
	 * Constructor.
	 *
	 * @param Provider    $provider Queue provider.
	 * @param mixed       $job      Job object.
	 * @param string|null $queue    Queue the job was stored in, when known.
	 */
	public function __construct( public Provider $provider, public $job, public ?string $queue = null ) {}
}
