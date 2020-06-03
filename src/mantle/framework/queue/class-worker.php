<?php
namespace Mantle\Framework\Queue;

use Mantle\Framework\Contracts\Queue\Queue_Manager;

class Worker {
	/**
	 * Queue Manager
	 *
	 * @var Queue_Manager
	 */
	protected $manager;

	/**
	 * Constructor.
	 *
	 * @param Queue_Manager $manager Manager instance.
	 */
	public function __construct( Queue_Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Run a batch of queue items.
	 *
	 * @param int $size Size of the batch to run.
	 */
	public function run_batch( int $size ) {
		$provider = $this->manager->get_provider();

		for ( $i = 0; $i < $size; $i++ ) {
			$job = $provider->pop();

			if ( ! $job ) {
				continue;
			}

			$job->handle();
		}
	}
}
