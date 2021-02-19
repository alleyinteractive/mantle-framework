<?php
/**
 * File_Helpers trait file.
 *
 * @package Mantle
 */

namespace Mantle\Filesystem;

use Mantle\Support\Str;

/**
 * File helpers.
 */
trait File_Helpers {
	/**
	 * The cache copy of the file's hash name.
	 *
	 * @var string
	 */
	protected $hash_name = null;

	/**
	 * Get the fully qualified path to the file.
	 *
	 * @return string
	 */
	public function path(): string {
		return $this->getRealPath();
	}

	/**
	 * Get the file's extension.
	 *
	 * @return string
	 */
	public function extension(): string {
		return $this->guessExtension();
	}

	/**
	 * Get a filename for the file.
	 *
	 * @param string|null $path File path.
	 * @return string
	 */
	public function hash_name( string $path = null ): string {
		if ( $path ) {
			$path = rtrim( $path, '/' ) . '/';
		}

		$hash      = $this->hash_name ?: $this->hash_name = Str::random( 40 );
		$extension = $this->guessExtension();

		if ( $extension ) {
			$extension = '.' . $extension;
		}

		return $path . $hash . $extension;
	}
}
