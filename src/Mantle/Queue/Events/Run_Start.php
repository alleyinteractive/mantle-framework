<?php
/**
 * Run_Start class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;
use Mantle\Support\Collection;

/**
 * Run Start Event
 */
class Run_Start {
	/**
	 * Constructor.
	 *
	 * @param Provider   $provider Queue provider.
	 * @param string     $queue Queue name.
	 * @param Collection $jobs Jobs to run.
	 */
	public function __construct( public Provider $provider, public string $queue, public Collection $jobs ) {
	}
}
