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
	 * @param int    $size Size of the batch to run.
	 * @param string $queue Queue name.
	 */
	public function run_batch( int $size, string $queue = null ) {
		$provider = $this->manager->get_provider();
		$jobs     = $provider->pop( $queue, $size );
		var_dump($jobs);

		foreach ( $jobs as $job ) {
			$job->handle();
		}
	}
}
