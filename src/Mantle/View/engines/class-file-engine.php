<?php
/**
 * File_Engine class file.
 *
 * @package Mantle
 */

namespace Mantle\View\Engines;

use Mantle\Contracts\View\Engine;

use function Mantle\Support\Helpers\validate_file;

/**
 * File Engine to load raw view files.
 */
class File_Engine implements Engine {
	/**
	 * Evaluate the contents of a view at a given path.
	 *
	 * @param string $path View path.
	 * @param array  $data View data.
	 */
	public function get( string $path, array $data = [] ): string {
		if ( 0 === validate_file( $path ) && 0 === validate_file( $path ) ) {
			return file_get_contents( $path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		}

		return '';
	}
}
