<?php
/**
 * Providers_Registered class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Events;

/**
 * Providers Registered Event
 */
class Providers_Registered {
	/**
	 * Constructor.
	 *
	 * @param \Mantle\Contracts\Queue\Queue_Manager $manager Queue manager.
	 */
	public function __construct( public \Mantle\Contracts\Queue\Queue_Manager $manager ) {}
}
