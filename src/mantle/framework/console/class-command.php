<?php
/**
 * Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use WP_CLI;

/**
 * CLI Command for Service Providers
 */
abstract class Command {
	/**
	 * Prefix for the command.
	 *
	 * @var string
	 */
	public const PREFIX = 'mantle';

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = '';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Command synopsis.
	 *
	 * @var string|array
	 */
	protected $synopsis = '';

	/**
	 * Command Arguments (generated at run-time).
	 *
	 * @var array
	 */
	protected $command_args;

	/**
	 * Command Flags (generated at run-time).
	 *
	 * @var array
	 */
	protected $command_flags;

	/**
	 * Register the command with wp-cli.
	 *
	 * @throws InvalidCommandException Thrown for a command without a name, incorrectly.
	 */
	public function register() {
		$name = $this->get_name();

		if ( empty( $name ) ) {
			throw new InvalidCommandException( 'Command missing name.' );
		}

		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			throw new InvalidCommandException( 'Cannot register wp-cli command when not in wp-cli mode.' );
		}

		WP_CLI::add_command(
			static::PREFIX . ' ' . $name,
			[ $this, 'callback' ],
			static::get_wp_cli_command_args()
		);
	}

	/**
	 * Getter for the command name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get command arguments.
	 *
	 * @return array
	 */
	protected function get_wp_cli_command_args(): array {
		return [
			'longdesc'  => $this->description,
			'shortdesc' => $this->short_description ? $this->short_description : $this->description,
			'synopsis'  => $this->synopsis,
		];
	}

	/**
	 * Set the command's arguments.
	 *
	 * @param array $args Arguments for the command.
	 */
	protected function set_command_args( array $args ) {
		$this->command_args = $args;
	}

	/**
	 * Set the command's flags.
	 *
	 * @param array $flags Flags for the command.
	 */
	protected function set_command_flags( array $flags ) {
		$this->command_flags = $flags;
	}

	/**
	 * Callback for the command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function callback( array $args, array $assoc_args ) {
		$this->set_command_args( $args );
		$this->set_command_flags( $assoc_args );

		$this->handle( $args, $assoc_args );
	}

	/**
	 * Callback for the command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	abstract public function handle( array $args, array $assoc_args );

	/**
	 * Write to the console log.
	 *
	 * @param string $message Message to log.
	 */
	public function log( string $message ): void {
		WP_CLI::log( $message );
	}

	/**
	 * Write an error to the console log.
	 *
	 * @param string $message Message to prompt.
	 * @param bool   $exit Flag to exit the script, defaults to false.
	 */
	public function error( string $message, bool $exit = false ) {
		WP_CLI::error( $message, $exit );
	}

	/**
	 * Ask the user for input.
	 *
	 * @param string $question Question to prompt.
	 * @return string
	 */
	public function input( string $question = '' ): string {
		if ( function_exists( 'readline' ) ) {
			return readline( $question );
		}

		echo $question; // phpcs:ignore

		return (string) stream_get_line( STDIN, 1024, "\n" );
	}

	/**
	 * Prompt a user for input.
	 *
	 * Response is expected to be in a boolean format (yes/no).
	 *
	 * @param string $question Question to prompt.
	 * @param bool   $default Default value.
	 * @return bool
	 */
	public function prompt( string $question, bool $default = false ): bool {
		$question .= ' [Y/n] ';

		$answer = strtolower( $this->input( $question ) );

		if ( empty( $answer ) ) {
			return $default;
		}

		return 'y' === $answer || 'yes' === $answer;
	}

	/**
	 * Prompt the user for input but hide the answer from the console.
	 *
	 * @param string $question Question to ask.
	 * @param bool   $default Default value.
	 * @return mixed
	 */
	public function secret( string $question, $default = null ) {
		$this->log( $question );

		// Black text on black background.
		echo "\033[30;40m";
		$input = $this->input();
		echo "\033[0m";

		return $input ?? $default;
	}

	/**
	 * Get a command argument.
	 *
	 * @param int   $position Argument position.
	 * @param mixed $default_value Default value.
	 * @return mixed
	 */
	public function get_arg( int $position, $default_value = null ) {
		return $this->command_args[ $position ] ?? $default_value;
	}

	/**
	 * Get a flag value for the command.
	 *
	 * @param string $flag Flag to get.
	 * @param mixed  $default_value Default value.
	 * @return mixed
	 */
	public function get_flag( string $flag, $default_value = null ) {
		return $this->command_flags[ $flag ] ?? $default_value;
	}
}
