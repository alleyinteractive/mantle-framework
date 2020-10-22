<?php
/**
 * Filesystem_Adapter class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Filesystem;

use InvalidArgumentException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Mantle\Framework\Contracts\Filesystem\Filesystem;

use function Mantle\Framework\Helpers\collect;

/**
 * Filesystem to Flysystem Adapter
 *
 * @mixin \League\Flysystem\FilesystemInterface
 */
class Filesystem_Adapter implements Filesystem {
	/**
	 * Filesystem instance.
	 *
	 * @var FilesystemInterface
	 */
	protected $driver;

	/**
	 * Constructor.
	 *
	 * @param FilesystemInterface $driver Filesystem instance.
	 */
	public function __construct( FilesystemInterface $driver ) {
		$this->driver = $driver;
	}

	/**
	 * Get all (recursive) of the directories within a given directory.
	 *
	 * @param  string $directory Directory name.
	 * @return string[]
	 */
	public function all_directories( string $directory = null ): array {
		return $this->directories( $directory, true );
	}

	/**
	 * Get all the directories within a given directory.
	 *
	 * @param string $directory Directory name.
	 * @param bool   $recursive Flag if it should be recursive.
	 * @return array
	 */
	public function directories( string $directory = null, bool $recursive = false ): array {
		$contents = $this->driver->listContents( $directory, $recursive );

		return $this->filter_contents_by_type( $contents, 'dir' );
	}

	/**
	 * Create a directory.
	 *
	 * @param string $path Path to create.
	 * @return bool
	 */
	public function make_directory( string $path ): bool {
		return $this->driver->createDir( $path );
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory Directory name.
	 * @return bool
	 */
	public function delete_directory( string $directory ): bool {
		return $this->driver->deleteDir( $directory );
	}

	/**
	 * Get all of the files from the given directory (recursive).
	 *
	 * @param string $directory Directory name.
	 * @return string[]
	 */
	public function all_files( string $directory = null ): array {
		return $this->files( $directory, true );
	}

	/**
	 * Get an array of all files in a directory.
	 *
	 * @param string $directory Directory name.
	 * @param bool   $recursive Flag if recursive.
	 * @return string[]
	 */
	public function files( string $directory = null, bool $recursive = false ): array {
		$contents = $this->driver->listContents( $directory, $recursive );
		return $this->filter_contents_by_type( $contents, 'file' );
	}

	/**
	 * Copy a file from one location to another.
	 *
	 * @param string $from From location.
	 * @param string $to To location.
	 * @return bool
	 */
	public function copy( string $from, string $to ): bool {
		return $this->driver->copy( $from, $to );
	}

	/**
	 * Move a file from a location to another.
	 *
	 * @param string $from From location.
	 * @param string $to To location.
	 * @return bool
	 */
	public function move( string $from, string $to ): bool {
		return $this->driver->rename( $from, $to );
	}

	/**
	 * Delete a file at the given paths.
	 *
	 * @param string|string[] $paths File paths.
	 * @return bool
	 */
	public function delete( $paths ): bool {
		$paths   = is_array( $paths ) ? $paths : func_get_args();
		$success = true;

		foreach ( $paths as $path ) {
			try {
				if ( ! $this->driver->delete( $path ) ) {
					$success = false;
				}
			} catch ( FileNotFoundException $e ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Check if a file exists at a current path.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function exists( string $path ): bool {
		return $this->driver->has( $path );
	}

	/**
	 * Check if a file is missing at a given path.
	 *
	 * @param string $path File path.
	 * @return bool
	 */
	public function missing( string $path ): bool {
		return ! $this->exists( $path );
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param string $path File path.
	 * @return string|bool
	 */
	public function get( string $path ) {
		return $this->driver->read( $path );
	}

	/**
	 * Get the file's last modification time.
	 *
	 * @param string $path File path.
	 * @return int|bool
	 */
	public function last_modified( string $path ) {
		return $this->driver->getTimestamp( $path );
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param string          $path File path.
	 * @param string|resource $contents File contents.
	 * @param array|string    $options  Options for the files or a string visibility.
	 * @return bool
	 */
	public function put( string $path, $contents, $options = [] ): bool {
		$options = is_string( $options )
			? [ 'visibility' => $options ]
			: (array) $options;

		// todo: add support for uploaded file.

		return is_resource( $contents )
			? $this->driver->putStream( $path, $contents, $options )
			: $this->driver->put( $path, $contents, $options );
	}

	/**
	 * Retrieve the size of the file.
	 *
	 * @param string $path File path.
	 * @return int|bool
	 */
	public function size( string $path ) {
		return $this->driver->getSize( $path );
	}

	/**
	 * Read a file through a stream.
	 *
	 * @param string $path File path.
	 * @return resource|false The path resource or false on failure.
	 */
	public function read_stream( string $path ) {
		return $this->driver->readStream( $path );
	}

	/**
	 * Write a file through a stream.
	 *
	 * @param string       $path File path.
	 * @param resource     $resource File resource.
	 * @param array|string $options File options or string visibility.
	 * @return bool
	 */
	public function write_stream( string $path, $resource, $options = [] ): bool {
		$options = is_string( $options )
			? [ 'visibility' => $options ]
			: (array) $options;

		return $this->driver->writeStream( $path, $resource, $options );
	}

	/**
	 * Retrieve a file's visibility.
	 *
	 * @param string $path
	 * @return string
	 */
	public function get_visibility( string $path ): string {
		if ( $this->driver->getVisibility( $path ) === AdapterInterface::VISIBILITY_PUBLIC ) {
			return Filesystem::VISIBILITY_PUBLIC;
		}

		return Filesystem::VISIBILITY_PRIVATE;
	}

	/**
	 * Set the visibility for a file.
	 *
	 * @param string $path Path to set.
	 * @param string $visibility Visibility to set.
	 * @return bool
	 */
	public function set_visibility( string $path, string $visibility ): bool {
		return $this->driver->setVisibility( $path, $this->parse_visibility( $visibility ) );
	}

	/**
	 * Parse the given visibility value.
	 *
	 * @param string $visibility Visibility to set.
	 * @return string
	 *
	 * @throws InvalidArgumentException Thrown on invalid visibility.
	 */
	protected function parse_visibility( string $visibility ): string {
		switch ( $visibility ) {
			case Filesystem::VISIBILITY_PUBLIC:
				return AdapterInterface::VISIBILITY_PUBLIC;
			case Filesystem::VISIBILITY_PRIVATE:
				return AdapterInterface::VISIBILITY_PRIVATE;
		}

		throw new InvalidArgumentException( "Unknown visibility: {$visibility}." );
	}

	/**
	 * Filter directory contents by type.
	 *
	 * @param array  $contents Content sto filter.
	 * @param string $type
	 * @return array
	 */
	protected function filter_contents_by_type( array $contents, string $type ): array {
		return collect( $contents )
			->where( 'type', $type )
			->pluck( 'path' )
			->values()
			->all();
	}

	/**
	 * Pass dynamic methods call onto Flysystem instance.
	 *
	 * @param string $method Method to call.
	 * @param array  $parameters Parameters for the driver.
	 * @return mixed
	 */
	public function __call( $method, array $parameters ) {
		return $this->driver->{$method}( ...$parameters );
	}
}
