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
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Register the command with wp-cli.
	 *
	 * @throws InvalidCommandException Thrown for a command without a name, incorrectly.
	 */
	public function register() {
		if ( empty( $this->name ) ) {
			throw new InvalidCommandException( 'Command missing name.' );
		}

		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			throw new InvalidCommandException( 'Cannot register wp-cli command when not in wp-cli mode.' );
		}

		WP_CLI::add_command( static::PREFIX . ' ' . $this->name, [ $this, 'handle' ] );
	}

	/**
	 * Callback for the command.
	 */
	abstract public function handle();

	/**
	 * Write to the console log.
	 *
	 * @param string $message Message to log.
	 */
	public function log( string $message ): void {
		\WP_CLI::log( $message );
	}

	/**
	 * Ask the user for input.
	 *
	 * @param string $question Question to prompt.
	 * @return string
	 */
	public function input( string $question ): string {
		if ( function_exists( 'readline' ) ) {
			return readline( $question );
		}

		echo $question; // phpcs:ignore

		$ret = stream_get_line( STDIN, 1024, "\n" );
		if ( \WP_Utils\Utils\is_windows() && "\r" === substr( $ret, -1 ) ) {
			$ret = substr( $ret, 0, -1 );
		}

		return $ret;
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
}
