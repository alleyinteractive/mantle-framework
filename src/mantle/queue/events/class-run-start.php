<?php
/**
 * Run_Start class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

use Mantle\Contracts\Queue\Provider;

/**
 * Run Start Event
 */
class Run_Start {
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
