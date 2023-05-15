<?php
/**
 * Job_Event class file.
 *
 * @package Mantle
 */

namespace Mantle\Scheduling;

use DateTimeZone;
use Mantle\Contracts\Application;
use Mantle\Framework\Exceptions\Handler;
use Throwable;

/**
 * Job Event
 *
 * Allow a job to be run on a specific schedule.
 */
class Job_Event extends Event {
	/**
	 * The associated command arguments (flags).
	 *
	 * @var array
	 */
	public $assoc_args;

	/**
	 * Run the event.
	 *
	 * @param Application $container Container instance.
	 */
	public function run( Application $container ) {
		if ( ! $this->filters_pass( $container ) ) {
			return;
		}

		$this->call_before_callbacks( $container );

		$instance = $container->make( $this->callback );

		try {
			$instance->handle( $this->parameters );

			$this->exit_code = 0;
		} catch ( Throwable $e ) {
			$container->make( Handler::class )->report( $e );

			$this->exception = $e;
			$this->exit_code = 1;
		}

		$this->call_after_callbacks( $container );
	}
}
