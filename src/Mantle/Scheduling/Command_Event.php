<?php
/**
 * Command_Event class file.
 *
 * @package Mantle
 */

namespace Mantle\Scheduling;

use DateTimeZone;
use Mantle\Console\Command;
use Mantle\Contracts\Application;
use Mantle\Contracts\Exceptions\Handler;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
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
	 * @var array<string, string>
	 */
	public $assoc_args;

	/**
	 * Constructor.
	 *
	 * @param string                $command Command class to run.
	 * @param array<mixed>          $parameters Arguments for the command.
	 * @param array<string, string> $assoc_args Associated arguments for the command.
	 * @param DateTimeZone|null     $timezone Timezone for the event.
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
	#[\Override]
	public function run( Application $container ): void {
		if ( ! $this->filters_pass( $container ) ) {
			return;
		}

		$this->call_before_callbacks( $container );


		try {
			$this->exit_code = 0;

			if ( is_callable( $this->callback ) ) {
				call_user_func( $this->callback, $this->parameters, $this->assoc_args );
			} else {
				$instance = $container->make( $this->callback );

				if ( $instance instanceof Command ) {
					// Run through the console lifecycle so the command's container, input, and output are set.
					$instance->set_container( $container );

					$this->exit_code = $instance->run( $this->make_input( $instance ), new NullOutput() );
				} else {
					$instance->handle( $this->parameters, $this->assoc_args );
				}
			}
		} catch ( Throwable $e ) {
			$container->make( Handler::class )->report( $e );

			$this->exception = $e;
			$this->exit_code = 1;
		}

		$this->call_after_callbacks( $container );
	}

	/**
	 * Build the console input for the command from the event's arguments.
	 *
	 * Positional parameters map onto the command's arguments in order; string
	 * keys and the associated arguments are passed through by name.
	 *
	 * @param Command $command Command instance.
	 */
	protected function make_input( Command $command ): ArrayInput {
		$definition = $command->getDefinition();
		$input      = [];

		foreach ( array_values( $definition->getArguments() ) as $index => $argument ) {
			if ( array_key_exists( $index, $this->parameters ) ) {
				$input[ $argument->getName() ] = $this->parameters[ $index ];
			}
		}

		foreach ( $this->parameters as $key => $value ) {
			if ( is_string( $key ) ) {
				$input[ $key ] = $value;
			}
		}

		foreach ( $this->assoc_args as $key => $value ) {
			$input[ '--' . ltrim( (string) $key, '-' ) ] = $value;
		}

		return new ArrayInput( $input, $definition );
	}
}
