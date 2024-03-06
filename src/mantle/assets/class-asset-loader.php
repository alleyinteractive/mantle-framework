<?php
/**
 * Asset_Loader class file.
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use Mantle\Assets\Exception\Asset_Not_Found;
use Mantle\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\validate_file;

/**
 * Mantle Asset Loader
 */
class Asset_Loader {
	/**
	 * Constructor.
	 *
	 * @param string $build_directory Directory to read from, optional.
	 * @param string $base_url        Base URL to use, optional.
	 */
	public function __construct(
		protected ?string $build_directory = null,
		protected ?string $base_url = null,
	) {
		if ( is_null( $this->build_directory ) ) {
			$this->build_directory = base_path( config( 'asset.path', 'build' ) );
		}

		if ( is_null( $this->base_url ) ) {
			$this->base_url = config( 'assets.url', '/' );
		}
	}

	/**
	 * Check if a asset exists.
	 *
	 * @param string $path Asset path.
	 */
	public function exists( string $path ): bool {
		return file_exists( $this->build_directory . $path );
	}

	/**
	 * Get the path to a versioned asset.
	 *
	 * @param  string $path Asset file path.
	 *
	 * @throws Asset_Not_Found Thrown on missing manifest or asset.
	 */
	public function path( string $path ): ?string {
		if ( ! file_exists( $this->build_directory . $path ) ) {
			$exception = new Asset_Not_Found( "Unable to locate asset file: {$path} in {$this->build_directory}{$path }." );

			if ( ! app( 'config' )->get( 'app.debug' ) ) {
				report( $exception );

				return null;
			} else {
				throw $exception;
			}
		}

		$version = $this->version( $path );

		if ( ! empty( $version ) ) {
			$path .= "?id={$version}";
		}

		return $path;
	}

	/**
	 * Get a URL to the versioned asset.
	 *
	 * @param string $path Asset path.
	 */
	public function url( string $path ): ?string {
		$path = $this->path( $path );

		if ( ! $path ) {
			return null;
		}

		return $this->base_url . $path;
	}

	/**
	 * Retrieve the dependencies for an asset.
	 *
	 * @param string $path Asset path.
	 */
	public function dependencies( string $path ): array {
		return $this->details( $path )['dependencies'] ?? [];
	}

	/**
	 * Retrieve the version for an asset.
	 *
	 * @param string $path Asset path.
	 */
	public function version( string $path ): string {
		return (string) ( $this->details( $path )['version'] ?? '' );
	}

	/**
	 * Retrieve an asset file from @wordpress/dependency-extraction-webpack-plugin.
	 *
	 * The method supports taking a asset path (such as app.js or app.css) and
	 * will attempt to find the asset file in the build directory. The asset file
	 * is a PHP file with the asset's path suffixed with `.asset.php`.
	 *
	 * @param string $path Asset path.
	 */
	protected function details( string $path ): array {
		// Attempt to cleanup the asset "path" to match the manifest name for the
		// asset file. The asset file is a PHP file with the asset's path suffixed
		// with `.asset.php`.
		if ( Str::contains( $path, '.' ) ) {
			// Remove the file extension.
			$path = Str::before_last( $path, '.' );
		}

		$details_file = "{$this->build_directory}/{$path}.asset.php";

		static $cache = [];

		if ( isset( $cache[ $details_file ] ) ) {
			return $cache[ $details_file ];
		}

		if ( file_exists( $details_file ) && 0 === validate_file( $details_file ) ) {
			$cache[ $details_file ] = require $details_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

			return $cache[ $details_file ];
		}

		return [];
	}

	/**
	 * Read the available block index.php files from the build directory.
	 *
	 * @return string[]
	 */
	public function blocks(): array {
		if ( ! is_dir( $this->build_directory ) ) {
			return [];
		}

		$finder = ( new Finder() )
			->files()
			->name( 'index.php' )
			->in( $this->build_directory )
			->depth( 1 );

		return collect( $finder )
			->map( fn ( SplFileInfo $file ) => $file->getRealPath() )
			->values()
			->all();
	}

	/**
	 * Retrieve the path to an asset when invoked.
	 *
	 * @param string $path Asset path.
	 * @param string $build_directory Build directory, optional.
	 */
	public function __invoke( string $path, ?string $build_directory = null ): string {
		if ( $build_directory ) {
			return ( new static( $build_directory ) )->url( $path );
		}

		return $this->url( $path );
	}
}
