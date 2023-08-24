<?php
/**
 * Filesystem_Adapter class file.
 *
 * @package Mantle
 */

namespace Mantle\Filesystem;

use InvalidArgumentException;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Mantle\Contracts\Filesystem\Filesystem;
use Mantle\Http\Uploaded_File;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\throw_if;

/**
 * Filesystem to Flysystem Adapter
 *
 * @todo Ensure config is passed.
 *
 * @mixin \League\Flysystem\FilesystemOperator
 */
class Filesystem_Adapter implements Filesystem {
	/**
	 * Constructor.
	 *
	 * @param FilesystemOperator $driver Filesystem instance.
	 * @param FilesystemAdapter $adapter Filesystem adapter.
	 * @param array $config Filesystem configuration.
	 */
	public function __construct(
		protected FilesystemOperator $driver,
		protected FilesystemAdapter $adapter,
		protected array $config = [],
	) {
	}

	/**
	 * Assert that the given file exists.
	 *
	 * @param string[]|string $path File path.
	 * @return static
	 */
	public function assertExists( $path ) {
		$paths = Arr::wrap( $path );

		foreach ( $paths as $path ) {
			PHPUnit::assertTrue(
				$this->exists( $path ),
				"Unable to find a file at path [{$path}]."
			);
		}

		return $this;
	}

	/**
	 * Assert that the given file does not exist.
	 *
	 * @param  string[]|string $path File path.
	 * @return static
	 */
	public function assertMissing( $path ) {
		$paths = Arr::wrap( $path );

		foreach ( $paths as $path ) {
				PHPUnit::assertFalse(
					$this->exists( $path ),
					"Found unexpected file at path [{$path}]."
				);
		}

		return $this;
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
		try {
			$this->driver->createDirectory( $path );
		} catch ( UnableToCreateDirectory $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory Directory name.
	 * @return bool
	 */
	public function delete_directory( string $directory ): bool {
		try {
			$this->driver->deleteDirectory( $directory );
		} catch ( UnableToDeleteDirectory $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
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
		try {
			$this->driver->copy( $from, $to );
		} catch ( UnableToCopyFile $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
	}

	/**
	 * Move a file from a location to another.
	 *
	 * @param string $from From location.
	 * @param string $to To location.
	 * @return bool
	 */
	public function move( string $from, string $to ): bool {
		try {
			$this->driver->move( $from, $to );
		} catch ( UnableToMoveFile $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
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
				$this->driver->delete( $path );
			} catch ( UnableToDeleteFile $e ) {
				throw_if( $this->throws_exceptions(), $e );

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
	 * Get the full path for the file at the given "short" path.
	 *
	 * @param string $path File path.
	 * @return string
	 */
	public function path( string $path ): string {
		if ( method_exists( $this->adapter, 'getPathPrefix' ) ) {
			return $adapter->getPathPrefix() . $path;
		}

		return $path;
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param string $path File path.
	 * @return string|bool
	 */
	public function get( string $path ) {
		try {
			return $this->driver->read( $path );
		} catch ( UnableToReadFile $e ) {
			throw_if( $this->throws_exceptions(), $e );
		}
	}

	/**
	 * Create a streamed response for a given file.
	 *
	 * @param string      $path File path.
	 * @param string|null $name File name.
	 * @param array       $headers Headers to include.
	 * @param string      $disposition File disposition.
	 * @return StreamedResponse
	 */
	public function response( string $path, ?string $name = null, array $headers = [], string $disposition = 'inline' ): StreamedResponse {
		$response = new StreamedResponse();
		$filename = $name ?? basename( $path );

		$disposition = $response->headers->makeDisposition(
			$disposition,
			$filename,
			$this->fallback_name( $filename )
		);

		$response->headers->replace(
			$headers + [
				'Content-Disposition' => $disposition,
				'Content-Length'      => $this->size( $path ),
				'Content-Type'        => $this->mime_type( $path ),
			]
		);

		$response->setCallback(
			function () use ( $path ) {
				$stream = $this->readStream( $path );
				fpassthru( $stream );
				fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		);

		return $response;
	}

	/**
	 * Create a streamed download response for a given file.
	 *
	 * @param string      $path File path.
	 * @param string|null $name File name.
	 * @param array       $headers HTTP headers.
	 * @return StreamedResponse
	 */
	public function download( $path, $name = null, array $headers = [] ): StreamedResponse {
		return $this->response( $path, $name, $headers, 'attachment' );
	}

	/**
	 * Convert the string to ASCII characters that are equivalent to the given name.
	 *
	 * @param string $name Fallback name.
	 * @return string
	 */
	protected function fallback_name( string $name ): string {
		return str_replace( '%', '', Str::ascii( $name ) );
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
	 * Get the mime-type of a given file.
	 *
	 * @param string $path File path.
	 * @return string|false
	 */
	public function mime_type( string $path ) {
		return $this->driver->getMimetype( $path );
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param string                                             $path File path.
	 * @param string|File|Uploaded_File|StreamInterface|resource $contents File contents.
	 * @param array|string                                       $options  Options for the files or a string visibility.
	 * @return bool
	 */
	public function put( string $path, $contents, $options = [] ): bool {
		$options = is_string( $options )
			? [ 'visibility' => $options ]
			: (array) $options;

		if (
			$contents instanceof File
			|| $contents instanceof Uploaded_File
		) {
			return $this->put_file( $path, $contents, $options ) ? true : false;
		}

		if ( $contents instanceof StreamInterface ) {
			$this->driver->writeStream( $path, $contents->detach(), $options );

			return true;
		}

		is_resource( $contents )
			? $this->driver->writeStream( $path, $contents, $options )
			: $this->driver->write( $path, $contents, $options );

		return true;
	}

	/**
	 * Store the uploaded file on the disk.
	 *
	 * @param string                                 $path File path.
	 * @param File|\Mantle\Http\Uploaded_File|string $file File object.
	 * @param mixed                                  $options Options.
	 * @return string|false
	 */
	public function put_file( string $path, $file, $options = [] ): string|bool {
		$file = is_string( $file ) ? new File( $file ) : $file;

		return $this->put_file_as( $path, $file, $file->hash_name(), $options );
	}

	/**
	 * Store the uploaded file on the disk with a given name.
	 *
	 * @param  string                                 $path File path.
	 * @param  File|\Mantle\Http\Uploaded_File|string $file File object.
	 * @param  string                                 $name File name.
	 * @param  mixed                                  $options Options.
	 * @return string|false
	 */
	public function put_file_as( string $path, $file, string $name, $options = [] ): string|bool {
		$stream = fopen( is_string( $file ) ? $file : $file->getRealPath(), 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$path   = trim( $path . '/' . $name, '/' );

		// Next, we will format the path of the file and store the file using a stream since
		// they provide better performance than alternatives. Once we write the file this
		// stream will get closed automatically by us so the developer doesn't have to.
		$result = $this->put(
			$path,
			$stream,
			$options
		);

		if ( is_resource( $stream ) ) {
			fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		}

		return $result ? $path : false;
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
	 * {@inheritdoc}
	 */
	public function readStream( $path ) {
		try {
			return $this->driver->readStream( $path );
		} catch ( UnableToReadFile $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}
	}

	/**
	 * Read a file through a stream.
	 *
	 * @param string $path File path.
	 * @return resource|false The path resource or false on failure.
	 */
	public function read_stream( string $path ) {
		return $this->readStream( $path );
	}

	/**
	 * {@inheritdoc}
	 */
	public function writeStream( string $path, $resource, $options = [] ): bool {
		$options = is_string( $options )
			? [ 'visibility' => $options ]
			: (array) $options;

		try {
			$this->driver->writeStream( $path, $resource, $options );
		} catch( UnableToWriteFile | UnableToSetVisibility $e ) {
			throw_if( $this->throws_exceptions(), $e );

			return false;
		}

		return true;
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
		return $this->writeStream( $path, $resource, $options );
	}

	/**
	 * Retrieve a file's visibility.
	 *
	 * @param string $path
	 * @return string
	 */
	public function get_visibility( string $path ): string {
		if ( $this->driver->getVisibility( $path ) === Visibility::PUBLIC ) {
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
	 * Prepend to a file.
	 *
	 * @param string $path File to prepend.
	 * @param string $data Data to prepend.
	 * @param string $separator Separator from existing data.
	 * @return bool
	 */
	public function prepend( string $path, string $data, string $separator = PHP_EOL ) {
		if ( $this->exists( $path ) ) {
			return $this->put( $path, $data . $separator . $this->get( $path ) );
		}

		return $this->put( $path, $data );
	}

	/**
	 * Append to a file.
	 *
	 * @param string $path File to append.
	 * @param string $data Data to append.
	 * @param string $separator Separator from existing data.
	 * @return bool
	 */
	public function append( $path, $data, $separator = PHP_EOL ) {
		if ( $this->exists( $path ) ) {
			return $this->put( $path, $this->get( $path ) . $separator . $data );
		}

		return $this->put( $path, $data );
	}

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param string $path Path to the file.
	 * @return string|null
	 *
	 * @throws RuntimeException Thrown on invalid filesystem adapter.
	 */
	public function url( string $path ): ?string {
		$adapter = $this->adapter;

		if ( method_exists( $adapter, 'getUrl' ) ) {
			return $adapter->getUrl( $path );
		} elseif ( method_exists( $this->driver, 'getUrl' ) ) {
			return $this->driver->getUrl( $path );
		} elseif ( $adapter instanceof AwsS3V3Adapter ) {
			return $this->get_aws_url( $adapter, $path );
		} elseif ( $adapter instanceof FtpAdapter ) {
			return $this->get_ftp_url( $path );
		} elseif ( $adapter instanceof LocalFilesystemAdapter ) {
			return $this->get_local_url( $path );
		} else {
			dd( 'no support', $adapter, $this->driver);
			throw new RuntimeException( 'This driver does not support retrieving URLs.' );
		}
	}

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param  \League\Flysystem\AwsS3v3\AwsS3Adapter $adapter Filesystem adapter.
	 * @param  string                                 $path File path.
	 * @return string
	 */
	protected function get_aws_url( AwsS3Adapter $adapter, string $path ): string {
		// If an explicit base URL has been set on the disk configuration then we will use
		// it as the base URL instead of the default path. This allows the developer to
		// have full control over the base path for this filesystem's generated
		// URLs.
		$url = $this->driver->getConfig()->get( 'url' );
		if ( ! is_null( $url ) ) {
			return $this->concatPathToUrl( $url, $adapter->getPathPrefix() . $path );
		}

		return $adapter->getClient()->getObjectUrl(
			$adapter->getBucket(),
			$adapter->getPathPrefix() . $path
		);
	}

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param  string $path File path.
	 * @return string
	 */
	protected function get_ftp_url( $path ) {
		$config = $this->driver->getConfig();

		return $config->has( 'url' )
			? $this->concatPathToUrl( $config->get( 'url' ), $path )
			: $path;
	}

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @param  string $path File path.
	 * @return string
	 */
	protected function get_local_url( $path ) {
		// If an explicit base URL has been set on the disk configuration then we will use
		// it as the base URL instead of the default path. This allows the developer to
		// have full control over the base path for this filesystem's generated URLs.
		if ( ! empty( $this->config['url'] ) ) {
			return $this->concatPathToUrl( $this->config['url'], $path );
		}

		return wp_upload_dir()['baseurl'] . $path;
	}

	/**
	 * Get a temporary URL for the file at the given path.
	 *
	 * @param  string             $path File path.
	 * @param  \DateTimeInterface $expiration File expiration.
	 * @param  array              $options Options for the URL.
	 * @return string
	 *
	 * @throws RuntimeException Thrown on missing temporary URL.
	 */
	public function temporary_url( string $path, $expiration, array $options = [] ): string {
		if ( method_exists( $this->adapter, 'getTemporaryUrl' ) ) {
			return $this->adapter->getTemporaryUrl( $path, $expiration, $options );
		} elseif ( $this->adapter instanceof AwsS3Adapter ) {
			return $this->getAwsTemporaryUrl( $this->adapter, $path, $expiration, $options );
		} else {
			throw new RuntimeException( 'This driver does not support creating temporary URLs.' );
		}
	}

	/**
	 * Get a temporary URL for the file at the given path.
	 *
	 * @param  AwsS3V3Adapter                         $adapter
	 * @param  string                                 $path
	 * @param  \DateTimeInterface                     $expiration
	 * @param  array                                  $options
	 * @return string
	 */
	public function getAwsTemporaryUrl( AwsS3V3Adapter $adapter, $path, $expiration, $options ) {
		$client = $adapter->getClient();

		$command = $client->getCommand(
			'GetObject',
			array_merge(
				[
					'Bucket' => $adapter->getBucket(),
					'Key'    => $adapter->getPathPrefix() . $path,
				],
				$options
			)
		);

		return (string) $client->createPresignedRequest(
			$command,
			$expiration
		)->getUri();
	}

	/**
	 * Concatenate a path to a URL.
	 *
	 * @param  string $url
	 * @param  string $path
	 * @return string
	 */
	protected function concatPathToUrl( $url, $path ) {
		return rtrim( $url, '/' ) . '/' . ltrim( $path, '/' );
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
	 * Determine if Flysystem exceptions should be thrown.
	 *
	 * @return bool
	 */
	protected function throws_exceptions(): bool {
		return (bool) ($this->config['throw'] ?? false);
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
