<?php
/**
 * Package_Manifest class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Manifest;

use Mantle\Application\Application_Exception;
use Mantle\Filesystem\Filesystem;

use function Mantle\Support\Helpers\collect;

/**
 * Package Manifest
 *
 * Retrieves third party packages from Composer dependencies.
 */
class Package_Manifest {
	/**
	 * Manifest from the disk.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	protected $manifest;

	/**
	 * Vendor folder path.
	 */
	protected string $vendor_path;

	/**
	 * Constructor.
	 *
	 * @param string $base_path     Base folder path for the Mantle site.
	 * @param string $manifest_path Path to the package manifest file.
	 */
	public function __construct( protected string $base_path, protected string $manifest_path ) {
		$this->vendor_path = $this->base_path . '/vendor';
	}

	/**
	 * Get all of the service provider class names for all packages.
	 *
	 * @return array<string, string>
	 */
	public function providers() {
		return $this->config( 'providers' );
	}

	/**
	 * Get all of the aliases for all packages.
	 *
	 * @return array<string, string>
	 */
	public function aliases() {
		return $this->config( 'aliases' );
	}

	/**
	 * Get all of the values for all packages for the given configuration name.
	 *
	 * @param string $key Key to retrieve.
	 * @return array<string, string>
	 */
	public function config( string $key ) {
		return collect( $this->get_manifest() )
			->flat_map(
				fn ( $configuration ) => (array) ( $configuration[ $key ] ?? [] )
			)
			->filter()
			->all();
	}

	/**
	 * Get the compiled manifest.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_manifest(): array {
		if ( $this->manifest !== null ) {
			return (array) $this->manifest;
		}

		// Skip when the manifest doesn't exist.
		if ( ! file_exists( $this->manifest_path ) ) {
			$this->manifest = [];

			return (array) $this->manifest;
		}

		$this->manifest = include $this->manifest_path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

		return (array) $this->manifest;
	}

	/**
	 * Build the manifest.
	 */
	public function build(): void {
		$filesystem = new Filesystem();

		$installed          = [];
		$composer_installed = $this->vendor_path . '/composer/installed.json';

		if ( $filesystem->exists( $composer_installed ) ) {
			$installed = json_decode(
				file_get_contents( $composer_installed ), // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
				true,
				512,
				JSON_THROW_ON_ERROR
			);

			$installed = $installed['packages'] ?? $installed;
		}

		$ignore = [];

		/**
		 * Process the installed packages:
		 *
		 * 1. Get all Mantle packages from Composer dependencies.
		 * 2. Remove all dependencies that the project has elected to ignore.
		 * 3. Remove all if the project has elected to ignore all.
		 * 4. Extract all service providers and aliases.
		 */
		$installed = collect( $installed )
			->map_with_keys(
				fn ( $package ) => [
					$this->format( $package['name'] ) => $package['extra']['mantle'] ?? [],
				]
			)
			->each(
				function ( array $configuration ) use ( &$ignore ): void {
					$ignore = array_merge( $ignore, $configuration['dont-discover'] ?? [] );
				}
			)
			->reject(
				fn ( $configuration, $package ) => in_array( $package, $ignore, true ),
			)
			->filter()
			->all();

		$this->write_manifest( $installed );
	}

	/**
	 * Write the manifest to the disk
	 *
	 * @param array<mixed> $manifest Manifest to write.
	 * @throws Application_Exception Thrown on error writing file.
	 */
	protected function write_manifest( array $manifest ): void {
		$filesystem = new Filesystem();

		$dir = dirname( $this->manifest_path );

		// Ensure the cached folder exists. Create the folder if it doesn't exist.
		if ( ! $filesystem->is_directory( $dir ) && ! $filesystem->make_directory( $dir ) ) {
			throw new Application_Exception( 'Unable to create path ' . $dir );
		}

		if ( ! $filesystem->put(
			$this->manifest_path,
			'<?php return ' . var_export( $manifest, true ) . ';' // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		) ) {
			throw new Application_Exception( 'Error writing file: ' . $this->manifest_path );
		}

		// Replace the manifest on the object.
		$this->manifest = $manifest;
	}

	/**
	 * Format the given package name.
	 *
	 * @param string $package Package name.
	 */
	protected function format( string $package ): string {
		return str_replace( $this->vendor_path . '/', '', $package );
	}

	/**
	 * Get all of the package names that should be ignored.
	 *
	 * @return array<string>
	 */
	protected function packages_to_ignore(): array {
		if ( ! file_exists( $this->base_path . '/composer.json' ) ) {
			return [];
		}

		return json_decode(
			file_get_contents( $this->base_path . '/composer.json' ),
			true
		)['extra']['mantle']['dont-discover'] ?? [];
	}
}
