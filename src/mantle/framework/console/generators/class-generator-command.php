<?php
/**
 * Generator_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Console\Command;
use Mantle\Support\Str;
use Mantle\Support\String_Replacements;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;
use Throwable;

/**
 * Generator Command
 */
abstract class Generator_Command extends Command {
	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{name}';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Prefix for the file.
	 *
	 * @var string
	 */
	protected $prefix = 'class-';

	/**
	 * String replacements.
	 *
	 * @var String_Replacements
	 */
	protected String_Replacements $replacements;

	/**
	 * Retrieve the generated class contents.
	 *
	 * @param string $name Class name.
	 * @return string
	 */
	abstract public function get_generated_class( string $name ): string;

	/**
	 * Command synopsis.
	 * Provides information to the user about how to use the generated file.
	 *
	 * @param string $name Class name.
	 */
	public function complete_synopsis( string $name ) { }

	/**
	 * Generator Command.
	 *
	 * @todo Replace with a filesystem abstraction.
	 */
	public function handle() {
		$this->replacements = new String_Replacements();

		$name = $this->argument( 'name' );

		if ( empty( $name ) ) {
			$this->error( 'Missing class name.' );

			return Command::FAILURE;
		}

		$path = $this->get_folder_path( $name );

		// Ensure the folder path exists.
		if ( ! is_dir( $path ) && ! mkdir( $path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $path );

			return Command::FAILURE;
		}

		$file_path = $this->get_file_path( $name );

		if ( file_exists( $file_path ) ) {
			$this->error( ( $this->type ?: ' File' ) . ' already exists: ' . $file_path );

			return Command::FAILURE;
		}

		// Store the generated class.
		try {
			if ( false === file_put_contents( $file_path, $this->get_generated_class( $name ) ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
				$this->error( 'Error writing to ' . $file_path );
			}
		} catch ( Throwable $e ) {
			dump( $e );

			$this->error( 'There was an error generating: ' . $e->getMessage() );

			return Command::FAILURE;
		}

		$this->log( ( $this->type ?: 'File' ) . ' created successfully: <info>' . $file_path . '</info>' );

		$this->complete_synopsis( $name );
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

		$parts[] = (string) $this->container->make( 'config' )->get( 'app.namespace', 'App' );
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

		$parts = array_merge(
			[
				Str::untrailing_slash( $this->get_base_path() ),
				strtolower( str_replace( '\\', '/', $this->type ) ),
			],
			[
				$parts,
			],
		);

		return Str::untrailing_slash( implode( '/', array_filter( $parts ) ) );
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
		$filename = Str::slug( str_replace( '_', '-', $filename ) );

		return $this->get_folder_path( $name ) . "/{$this->prefix}{$filename}.php";
	}

	/**
	 * Get the base path for the generated folder.
	 *
	 * @return string
	 */
	protected function get_base_path(): string {
		return $this->container->get_base_path() . '/app/';
	}

	/**
	 * Get the application's i18n domain.
	 *
	 * @return string
	 */
	protected function get_i18n_domain(): string {
		$domain = config( 'app.i18n_domain', environment( 'APP_I18N_DOMAIN', null ) );

		if ( $domain ) {
			return $domain;
		}

		// Attempt to calculate the domain from the application's folder.
		return Str::slug( basename( $this->container->get_base_path() ), 'mantle' );
	}

	/**
	 * Retrieve the string inflector to use.
	 *
	 * @return InflectorInterface
	 */
	protected function inflector(): InflectorInterface {
		// Use the bound inflector if available.
		if ( $this->container->bound( InflectorInterface::class ) ) {
			return $this->container->make( InflectorInterface::class );
		}

		return $this->container->make( EnglishInflector::class );
	}
}
