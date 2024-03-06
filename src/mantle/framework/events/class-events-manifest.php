<?php
/**
 * Events_Manifest class file
 *
 * @package Mantle
 */

namespace Mantle\Framework\Events;

use Mantle\Filesystem\Filesystem;
use Mantle\Application\Application_Exception;
use Mantle\Framework\Providers\Event_Service_Provider;

use function Mantle\Support\Helpers\collect;

/**
 * Events Manifest
 *
 * Manages the cached storage of registered event listeners.
 */
class Events_Manifest {
	/**
	 * Manifest from the disk.
	 *
	 * @var ?array
	 */
	protected $manifest;

	/**
	 * Base folder path.
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * Vendor folder path.
	 *
	 * @var string
	 */
	protected $vendor_path;

	/**
	 * Package Manifest file path.
	 *
	 * @var string
	 */
	protected $manifest_path;

	/**
	 * Constructor.
	 *
	 * @param string $manifest_path Path to the package manifest file.
	 */
	public function __construct( string $manifest_path ) {
		$this->manifest_path = $manifest_path;
	}

	/**
	 * Get all of events stored in the manifest.
	 *
	 * @return string[]
	 */
	public function events() {
		return $this->get_manifest();
	}

	/**
	 * Get the compiled manifest.
	 */
	protected function get_manifest(): array {
		if ( isset( $this->manifest ) ) {
			return (array) $this->manifest;
		}

		// Skip when the manifest doesn't exist.
		if ( ! file_exists( $this->manifest_path ) ) {
			$this->manifest = [];

			return (array) $this->manifest;
		}

		$this->manifest = include $this->manifest_path;

		return (array) $this->manifest;
	}

	/**
	 * Store the manifest from a set of events.
	 *
	 * @throws Application_Exception Thrown on missing event service provider.
	 */
	public function build(): void {
		// Delete the existing manifest if it exists.
		if ( file_exists( $this->manifest_path ) ) {
			unlink( $this->manifest_path ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
		}

		$provider = collect( app()->get_providers() )
			->filter( fn ( $provider ) => $provider instanceof Event_Service_Provider )
			->pop();

		if ( ! $provider ) {
			throw new Application_Exception( 'Missing provider: ' . Event_Service_Provider::class );
		}

		$this->write_manifest( $provider->get_events() );
	}

	/**
	 * Write the manifest to the disk
	 *
	 * @param array $manifest Manifest to write.
	 * @throws Application_Exception Thrown on error writing file.
	 */
	protected function write_manifest( array $manifest ) {
		$dir        = dirname( $this->manifest_path );
		$filesystem = new Filesystem();

		// Ensure the cached folder exists.
		if ( ! is_dir( $dir ) ) {
			// Create the folder if it doesn't exist.
			$filesystem->ensure_directory_exists( $dir );

			// Throw an exception if the folder doesn't exist.
			if ( ! $filesystem->is_directory( $dir ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
				throw new Application_Exception( 'Unable to create path ' . $dir );
			}
		}

		if ( ! $filesystem->put( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			$this->manifest_path,
			'<?php return ' . var_export( $manifest, true ) . ';' // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		) ) {
			throw new Application_Exception( 'Error writing file: ' . $this->manifest_path );
		}

		// Refresh the manifest.
		$this->manifest = $manifest;
	}
}
