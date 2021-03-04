<?php
/**
 * Stub_Generator_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Support\String_Replacements;

/**
 * Stub-based generator command.
 */
abstract class Stub_Generator_Command extends Generator_Command {
	/**
	 * Stub variables String Replacement instance.
	 *
	 * @var String_Replacements
	 */
	protected $replacements;

	/**
	 * File Stub
	 *
	 * @var string
	 */
	protected $stub;

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	abstract public function get_file_stub(): string;

	/**
	 * Retrieve the generated class contents.
	 *
	 * @param string $name Class name.
	 * @return string
	 */
	public function get_generated_class( string $name ): string {
		// Register replacements for the stub file.
		$this->replacements->add( '{{ class }}', $this->get_class_name( $name ) );
		$this->replacements->add( '{{ namespace }}', $this->get_namespace( $name ) );

		$contents = file_get_contents( $this->get_file_stub() ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return $this->replacements->replace( $contents );
	}
}
