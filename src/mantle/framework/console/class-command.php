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
}
