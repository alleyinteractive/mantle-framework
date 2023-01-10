<?php
/**
 * Run_Complete class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;
use Mantle\Support\Collection;

/**
 * Run Complete Event
 */
class Run_Complete {
	/**
	 * Constructor.
	 *
	 * @param Provider    $provider Queue provider.
	 * @param string|null $queue    Queue name.
	 * @param Collection  $jobs     Jobs that were run.
	 */
	public function __construct( public Provider $provider, public ?string $queue = null, Collection $jobs ) {
	}
}
