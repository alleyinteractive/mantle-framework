<?php
/**
 * Run_Start class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Queue\Events;

/**
 * Run Start Event
 */
class Run_Start {
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
