<?php
/**
 * Generator_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Framework\Contracts\Application as Application_Contract;
use Mantle\Framework\Providers\Provider_Exception;
use Mantle\Framework\Support\String_Replacements;

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
	 * Stub variables String Replacement instance.
	 *
	 * @var String_Replacements
	 */
	protected $replacements;

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

		$this->replacements = new String_Replacements();
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	abstract public function get_file_stub(): string;

	/**
	 * Command synopsis.
	 * Provides information to the user about how to use the generated file.
	 *
	 * @param string $name Class name.
	 */
	public function synopsis( string $name ) { }

	/**
	 * Generator Command.
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

		$path = $this->get_folder_path( $name );

		// Ensure the folder path exists.
		if ( ! is_dir( $path ) && ! mkdir( $path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $path );
		}

		$file_path = $this->get_file_path( $name );
		if ( file_exists( $file_path ) ) {
			$this->error( $this->type . ' already exists: ' . $file_path, true );
		}

		// Build the stub file and apply replacements.
		$this->build_stub( $name );
		$this->set_stub( $this->replacements->replace( $this->get_stub() ) );

		if ( false === file_put_contents( $file_path, $this->get_stub() ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			$this->error( 'Error writing to ' . $file_path );
		}

		$this->log( $this->type . ' created successfully: ' . $file_path );
		$this->synopsis( $name );
	}

	/**
	 * Build the generated file.
	 *
	 * @param string $name Class name to generate.
	 */
	protected function build_stub( string $name ) {
		$this->set_stub( file_get_contents( $this->get_file_stub() ) ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		// Register replacements for the stub file.
		$this->replacements->add( '{{ class }}', $this->get_class_name( $name ) );
		$this->replacements->add( '{{ namespace }}', $this->get_namespace( $name ) );
	}

	/**
	 * Get the file stub.
	 *
	 * @return string
	 */
	protected function get_stub(): string {
		return $this->stub;
	}

	/**
	 * Set the file stub.
	 *
	 * @param string $stub File stub contents.
	 * @return static
	 */
	protected function set_stub( string $stub ) {
		$this->stub = $stub;

		if ( empty( $this->stub ) ) {
			$this->error( 'Empty stub generated.', true );
		}

		return $this;
	}

	/**
	 * Get the class name to use.
	 *
	 * @param string $name Inputted name.
	 * @return string
	 */
	protected function get_class_name( string $name ): string {
		$parts = explode( '\\', $name );
		return array_pop( $parts );
	}

	/**
	 * Get the class' namespace.
	 *
	 * @param string $name Name to use.
	 * @return string
	 */
	protected function get_namespace( string $name ): string {
		$parts = [];

		// Remove the class name and get the namespace.
		$name = explode( '\\', $name );
		array_pop( $name );

		$parts[] = (string) $this->app->config->get( 'app.namespace', 'App' );
		$parts[] = $this->type;

		if ( ! empty( $name ) ) {
			$parts = array_merge( $parts, $name );
		}

		return implode( '\\', $parts );
	}

	/**
	 * Get the folder location of the file.
	 *
	 * @param string $name Name to use.
	 * @return string
	 */
	protected function get_folder_path( string $name ): string {
		$parts = explode( '\\', $name );

		array_pop( $parts );

		if ( ! empty( $parts ) ) {
			$parts = strtolower( str_replace( '_', '-', join( '/', $parts ) ) ) . '/';
		} else {
			$parts = '';
		}

		return \untrailingslashit( $this->get_base_path() . strtolower( str_replace( '\\', '/', $this->type ) ) . '/' . $parts );
	}

	/**
	 * Get the location for the generated file.
	 *
	 * @param string $name Name to use.
	 * @return string
	 */
	protected function get_file_path( string $name ): string {
		$parts    = explode( '\\', $name );
		$filename = array_pop( $parts );
		$filename = \sanitize_title_with_dashes( str_replace( '_', '-', $filename ) );

		return $this->get_folder_path( $name ) . '/class-' . $filename . '.php';
	}

	/**
	 * Get the base path for the generated folder.
	 *
	 * @return string
	 */
	protected function get_base_path(): string {
		return $this->app->get_base_path() . '/app/';
	}
}
