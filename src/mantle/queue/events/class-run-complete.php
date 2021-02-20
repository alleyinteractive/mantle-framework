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
	 * Queue provider.
	 *
	 * @var mixed
	 */
	public $provider;

	/**
	 * Queue Name
	 *
	 * @var string
	 */
	public $queue;

	/**
	 * Constructor.
	 *
	 * @param Provider $provider Queue provider.
	 * @param string   $queue Queue name.
	 */
	public function __construct( Provider $provider, $queue ) {
		$this->provider = $provider;
		$this->queue    = $queue;
	}
}
