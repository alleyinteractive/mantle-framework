<?php
/**
 * Job_Event class file.
 *
 * @package Mantle
 */

namespace Mantle\Scheduling;

use Mantle\Contracts\Container;
use Mantle\Framework\Exceptions\Handler;
use Throwable;

/**
 * Job Event
 *
 * Allow a job to be run on a specific schedule.
 */
class Job_Event extends Event {
	/**
	 * The job class.
	 *
	 * @var string
	 */
	public $job;

	/**
	 * The command arguments.
	 *
	 * @var array
	 */
	public $arguments;

	/**
	 * The associated command arguments (flags).
	 *
	 * @var array
	 */
	public $assoc_args;

	/**
	 * Constructor.
	 *
	 * @param string $job Job to run.
	 * @param array  $arguments Arguments for the command.
	 * @param string $timezone Timezone for the event.
	 */
	public function __construct( string $job, array $arguments = [], $timezone = null ) {
		parent::__construct( null, [], $timezone );

		$this->job       = $job;
		$this->arguments = $arguments;
	}

	/**
	 * Run the event.
	 *
	 * @param Container $container Container instance.
	 */
	public function run( Container $container ) {
		if ( ! $this->filters_pass( $container ) ) {
			return;
		}

		$this->call_before_callbacks( $container );

		$instance = $container->make( $this->job );

		try {
			$instance->handle( $this->arguments );

			$this->exit_code = 0;
		} catch ( Throwable $e ) {
			$container->make( Handler::class )->report( $e );

			$this->exception = $e;
			$this->exit_code = 1;
		}

		$this->call_after_callbacks( $container );
	}
}
