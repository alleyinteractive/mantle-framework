<?php
/**
 * Command_Event class file.
 *
 * @package Mantle
 */

namespace Mantle\Scheduling;

use DateTimeZone;
use Mantle\Contracts\Application;
use Mantle\Contracts\Exceptions\Handler;
use Throwable;

/**
 * Command Event
 *
 * Allow a command to be run on a specific schedule.
 */
class Command_Event extends Event {
	/**
	 * The associated command arguments (flags).
	 *
	 * @var array
	 */
	public $assoc_args;

	/**
	 * Constructor.
	 *
	 * @param string            $command Command class to run.
	 * @param array             $parameters Arguments for the command.
	 * @param array             $assoc_args Associated arguments for the command.
	 * @param DateTimeZone|null $timezone Timezone for the event.
	 */
	public function __construct( string $command, array $parameters = [], array $assoc_args = [], ?DateTimeZone $timezone = null ) {
		parent::__construct( $command, $parameters, $timezone );

		$this->assoc_args = $assoc_args;
	}

	/**
	 * Run the event.
	 *
	 * @param Application $container Container instance.
	 */
	public function run( Application $container ): void {
		if ( ! $this->filters_pass( $container ) ) {
			return;
		}

		$this->call_before_callbacks( $container );

		$instance = $container->make( $this->callback );

		try {
			$instance->handle( $this->parameters, $this->assoc_args );

			$this->exit_code = 0;
		} catch ( Throwable $e ) {
			$container->make( Handler::class )->report( $e );

			$this->exception = $e;
			$this->exit_code = 1;
		}

		$this->call_after_callbacks( $container );
	}
}
