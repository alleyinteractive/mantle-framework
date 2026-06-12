<?php
/**
 * Has_PSR_4_Folder_Path trait file
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use function Mantle\Support\Helpers\str;

/**
 * Generator support for PSR-4 files.
 *
 * @mixin \Mantle\Framework\Console\Generators\Generator_Command
 */
trait With_PSR_4_File {
	/**
	 * Get the class name to use.
	 *
	 * @param string $name Inputted name.
	 */
	protected function get_class_name( string $name ): string {
		$name = str( str( $name )->explode( '\\' )->pop() )->studly();

		if ( 'Tests' === $this->type && ! str( $name )->endsWith( 'Test' ) ) {
			$name = $name->append( 'Test' );
		}

		return $name->value();
	}

	/**
	 * Get the folder location of the file.
	 *
	 * @param string $name Name to use.
	 */
	protected function get_folder_path( string $name ): string {
		$parts = str( $name )->explode( '\\' );

		$parts->pop();

		$folder = $parts
			->filter()
			->map( fn ( string $part ) => str( $part )->studly()->value() )
			->implode( DIRECTORY_SEPARATOR );

		return str( $this->get_base_path() )
			->append( strtolower( str_replace( '\\', '/', $this->type ) ) . DIRECTORY_SEPARATOR )
			->append( $folder )
			->value();
	}

	/**
	 * Get the location for the generated file.
	 *
	 * @param string $name Name to use.
	 */
	protected function get_file_path( string $name ): string {
		$filename = str(
			str( $name )->explode( '\\' )->pop(),
		)
			->studly();

		// If the type is Tests and the filename doesn't end with Test, append it.
		if ( 'Tests' === $this->type && ! $filename->endsWith( 'Test' ) ) {
			$filename = $filename->append( 'Test' );
		}

		return str( $this->get_folder_path( $name ) )
			->trailingSlash()
			->append( $filename->append( '.php' ) )
			->value();
	}
}
