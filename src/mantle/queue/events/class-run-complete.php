<?php
/**
 * Run_Complete class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;

/**
 * Run Complete Event
 */
class Run_Complete {
	/**
	 * Constructor.
	 *
	 * @param Provider $provider Queue provider.
	 * @param string   $queue Queue name.
	 */
	public function __construct( public Provider $provider, public string $queue ) {
	}
}
