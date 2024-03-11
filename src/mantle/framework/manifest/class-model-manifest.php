<?php
/**
 * Model_Manifest class file
 *
 * @package Mantle
 */

namespace Mantle\Framework\Manifest;

use Mantle\Application\Application_Exception;
use Mantle\Contracts\Application;
use Mantle\Contracts\Database\Registrable;
use Mantle\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\collect;

/**
 * Model Manifest.
 *
 * Automatically registers models in the application.
 */
class Model_Manifest {
	/**
	 * Manifest from the disk.
	 */
	protected ?array $manifest = null;

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
	}

	/**
	 * Get all of the service provider class names for all packages.
	 *
	 * @return array
	 */
	public function models() {
		return $this->get_manifest();
	}

	/**
	 * Get the compiled manifest.
	 */
	protected function get_manifest(): array {
		if ( isset( $this->manifest ) ) {
			return $this->manifest;
		}

		// Skip when the manifest doesn't exist.
		if ( ! file_exists( $this->manifest_path ) ) {
			$this->manifest = [];

			return $this->manifest;
		}

		$this->manifest = include $this->manifest_path;

		return (array) $this->manifest;
	}

	/**
	 * Build the manifest.
	 */
	public function build(): void {
		// Delete the existing manifest if it exists.
		if ( file_exists( $this->manifest_path ) ) {
			unlink( $this->manifest_path ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
		}

		$manifest  = collect();
		$namespace = app()->get_namespace();

		$finder = Finder::create()
			->files()
			->name( '*.php' )
			->in( $this->base_path . '/models' );

		$filesystem = new Filesystem();

		foreach ( $finder as $file ) {
			$class = $namespace . '\\Models\\' . $filesystem->guess_class_name( $file->getRealPath() );

			if ( class_exists( $class ) && in_array( Registrable::class, class_implements( $class ), true ) ) {
				$manifest[] = $class;
			}
		}

		$this->write_manifest( $manifest->unique()->values()->all() );
	}

	/**
	 * Write the manifest to the disk
	 *
	 * @param array $manifest Manifest to write.
	 * @throws Application_Exception Thrown on error writing file.
	 */
	protected function write_manifest( array $manifest ) {
		$dir = dirname( $this->manifest_path );

		// Ensure the cached folder exists.
		if ( ! is_dir( $dir ) ) {
			// Create the folder if it doesn't exist.
			if ( ! mkdir( $dir ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
				throw new Application_Exception( 'Unable to create path ' . $dir );
			}
		}


		if ( ! file_put_contents( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			$this->manifest_path,
			'<?php return ' . var_export( $manifest, true ) . ';' // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		) ) {
			throw new Application_Exception( 'Error writing file: ' . $this->manifest_path );
		}

		// Refresh the manifest.
		$this->manifest = $manifest;
	}
}
