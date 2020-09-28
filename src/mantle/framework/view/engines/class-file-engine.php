<?php
/**
 * File_Engine class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\View\Engines;

use Mantle\Framework\Contracts\View\Engine;

/**
 * File Engine to load raw view files.
 */
class File_Engine implements Engine {
	/**
	 * @inheritDoc
	 */
	public function get( string $path, array $data = [] ): string {
		return file_get_contents( $path );
	}
}
