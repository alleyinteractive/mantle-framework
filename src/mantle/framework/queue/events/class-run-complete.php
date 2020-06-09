<?php
/**
 * Run_Complete class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue\Events;

/**
 * Run Complete Event
 */
class Run_Complete {
	/**
	 * Queue Name
	 *
	 * @var string
	 */
	public $queue;

	/**
	 * Constructor.
	 *
	 * @param string $queue Queue name.
	 */
	public function __construct( $queue ) {
		$this->queue = $queue;
	}
}
