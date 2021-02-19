<?php
/**
 * Command_Event class file.
 *
 * @package Mantle
 */

namespace Mantle\Scheduling;

use Mantle\Framework\Contracts\Container;
use Mantle\Framework\Exceptions\Handler;
use Throwable;

/**
 * Command Event
 *
 * Allow a command to be run on a specific schedule.
 */
class Command_Event extends Event {
	/**
	 * The command string.
	 *
	 * @var string
	 */
	public $command;

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
	 * @param string $command Command class to run.
	 * @param array  $arguments Arguments for the command.
	 * @param array  $assoc_args Associated arguments for the command.
	 * @param string $timezone Timezone for the event.
	 */
	public function __construct( string $command, array $arguments = [], array $assoc_args = [], $timezone = null ) {
		parent::__construct( null, [], $timezone );

		$this->command    = $command;
		$this->arguments  = $arguments;
		$this->assoc_args = $assoc_args;
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

		$instance = $container->make( $this->command );

		try {
			$instance->callback( $this->arguments, $this->assoc_args );

			$this->exit_code = 0;
		} catch ( Throwable $e ) {
			$container->make( Handler::class )->report( $e );

			$this->exception = $e;
			$this->exit_code = 1;
		}

		$this->call_after_callbacks( $container );
	}
}
