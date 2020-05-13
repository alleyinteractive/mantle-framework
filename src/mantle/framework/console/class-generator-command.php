<?php
/**
 * Generator_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Framework\Contracts\Application as Application_Contract;
use Mantle\Framework\Providers\Provider_Exception;

/**
 * Generator Command
 */
abstract class Generator_Command extends Command {
	/**
	 * File Stub
	 *
	 * @var string
	 */
	protected $stub;

	/**
	 * The application instance.
	 *
	 * @var \Mantle\Framework\Application
	 */
	protected $app;

	/**
	 * Command synopsis.
	 *
	 * @var string|array
	 */
	protected $synopsis = '<name>';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Constructor.
	 *
	 * @param Application_Contract $app Application contract.
	 * @throws Provider_Exception Thrown when generator doesn't have type set.
	 */
	public function __construct( Application_Contract $app ) {
		$this->app = $app;

		if ( empty( $this->type ) ) {
			throw new Provider_Exception( 'Generator needs a "type" set: ' . get_class( $this ) );
		}
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	abstract public function get_file_stub(): string;

	/**
	 * Generator Command.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name to use.
	 *
	 * @todo Replace with a filesystem abstraction.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		// Prevent command being run in non-local environments.
		if ( 'local' !== $this->app->environment() ) {
			$this->error( 'Generator cannot be used outside of local environment.', true );
		}

		if ( empty( $args[0] ) ) {
			$this->error( 'Missing class name.', true );
		}

		list( $name ) = $args;

		$folder_path = $this->get_folder_path( $name );

		// Ensure the folder exists.
		if ( ! is_dir( $folder_path ) && ! mkdir( $folder_path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $folder_path );
		}

		$file = $this->get_file_path( $name );

		if ( file_exists( $file ) ) {
			$this->error( $this->type . ' already exists: ' . $file, true );
		}

		$this
			->set_stub( file_get_contents( $this->get_file_stub() ) )
			->replace_stub_variable( 'class', $this->get_class_name( $name ) )
			->replace_stub_variable( 'namespace', $this->get_namespace( $name ) );

		if ( false === file_put_contents( $file, $this->stub ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			$this->error( 'Error writing to ' . $file );
		}

		$this->log( $this->type . ' created successfully: ' . $file );
	}

	/**
	 * Set the file stub.
	 *
	 * @param string $stub File stub contents.
	 * @return static
	 */
	protected function set_stub( string $stub ) {
		$this->stub = $stub;
		return $this;
	}

	/**
	 * Replace a variable in the stub.
	 *
	 * @param string $variable Variable to replace.
	 * @param string $value Value to replace with.
	 * @return static
	 */
	protected function replace_stub_variable( string $variable, string $value ) {
		$this->stub = str_replace( '{{ ' . $variable . ' }}', $value, $this->stub );
		return $this;
	}

	/**
	 * Get the class name to use.
	 *
	 * @param string $name Inputted name.
	 * @return string
	 */
	public function get_class_name( string $name ): string {
		$parts = explode( '\\', $name );
		return array_pop( $parts );
	}

	/**
	 * Get the application's namespace.
	 *
	 * @param string $name Name to use.
	 * @return string
	 */
	public function get_namespace( string $name ): string {
		// Remove the class name and get the namespace.
		$name = explode( '\\', $name );
		array_pop( $name );

		$root_namespace = (string) $this->app->config->get( 'app.namespace', 'App' );
		return $root_namespace . '\\' . $this->type . '\\' . implode( '\\', $name );
	}

	/**
	 * Get the folder location of the file.
	 *
	 * @param string $name Name to use.
	 * @return string
	 */
	public function get_folder_path( string $name ): string {
		$parts = explode( '\\', $name );

		array_pop( $parts );

		if ( ! empty( $parts ) ) {
			$parts = strtolower( str_replace( '_', '-', join( '/', $parts ) ) ) . '/';
		} else {
			$parts = '';
		}

		return $this->app->get_base_path()
			. '/app/' . strtolower( $this->type ) . '/'
			. $parts;
	}

	/**
	 * Get the location for the generated file.
	 *
	 * @param string $name Name to use.
	 * @return string
	 */
	public function get_file_path( string $name ): string {
		$parts = explode( '\\', $name );

		$filename = array_pop( $parts );
		$filename = strtolower( str_replace( '_', '-', $filename ) );

		return $this->get_folder_path( $name ) . 'class-' . strtolower( $filename ) . '.php';
	}
}
