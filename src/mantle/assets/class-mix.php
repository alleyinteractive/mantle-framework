<?php
/**
 * Mix class file.
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use Mantle\Assets\Exception\Mix_File_Not_Found;
use Mantle\Support\Str;

/**
 * Mix Asset Loader
 *
 * @deprecated Use ___ instead.
 */
class Mix {
	/**
	 * Storage of parsed Mix manifests.
	 *
	 * @var array
	 */
	protected static array $manifests = [];

	/**
	 * Manifest directory read from.
	 *
	 * @var string
	 */
	protected string $manifest_directory;

	/**
	 * Parsed manifest for the current instance.
	 *
	 * @var array
	 */
	protected array $manifest;

	/**
	 * Flag if HMR is enabled.
	 *
	 * @var bool
	 */
	protected bool $hmr = false;

	/**
	 * Read a manifest file from disk.
	 *
	 * @throws Mix_File_Not_Found Thrown on missing file.
	 *
	 * @param string $manifest_path Manifest file path.
	 * @return array
	 */
	protected static function manifest( string $manifest_path ): array {
		if ( isset( static::$manifests[ $manifest_path ] ) ) {
			return static::$manifests[ $manifest_path ];
		}

		if ( ! file_exists( $manifest_path ) ) {
			throw new Mix_File_Not_Found( $manifest_path );
		}

		static::$manifests[ $manifest_path ] = json_decode( file_get_contents( $manifest_path ), true ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return static::$manifests[ $manifest_path ];
	}

	/**
	 * Constructor.
	 *
	 * @param string $manifest_directory Manifest directory to read from, optional.
	 */
	public function __construct( string $manifest_directory = null ) {
		$this->read( $manifest_directory );
	}

	/**
	 * Read a manifest from a directory.
	 *
	 * @param string $manifest_directory Manifest directory to read from, optional.
	 * @return string
	 */
	public function read( string $manifest_directory = null ) {
		$asset_path = config( 'asset.path', 'build' );

		if ( ! $manifest_directory ) {
			$manifest_directory = base_path( $asset_path );
		}

		$this->manifest_directory = $manifest_directory;

		// Skip if HMR is enabled.
		if ( is_file( $manifest_directory . '/hot' ) ) {
			$this->hmr = true;
			return;
		}

		$manifest_name = config( 'assets.mix.manifest_name', 'mix-manifest.json' );

		$this->manifest = static::manifest( "{$manifest_directory}/{$manifest_name}" );
	}

	/**
	 * Get the path to a versioned Mix file.
	 *
	 * @param  string $path Mix file path.
	 * @return string
	 *
	 * @throws Mix_File_Not_Found Thrown on missing manifest or asset.
	 */
	public function path( string $path ): string {
		$asset_path = config( 'asset.path', 'build' );

		// Pass directly to HMR.
		if ( $this->hmr ) {
			$url = rtrim( file_get_contents( $this->manifest_directory . '/hot' ) );

			if ( Str::starts_with( $url, [ 'http://', 'https://' ] ) ) {
				return Str::after( $url, ':' ) . $path;
			}

			return "//localhost:8080/{$path}";
		}

		if ( ! isset( $this->manifest[ $path ] ) ) {
			$exception = new Mix_File_Not_Found( "Unable to locate Mix file: {$path}." );

			if ( ! app( 'config' )->get( 'app.debug' ) ) {
				report( $exception );

				return $path;
			} else {
				throw $exception;
			}
		}

		$asset_base_url = app( 'config' )->get( 'assets.url', null );

		if ( ! $asset_base_url ) {
			$asset_base_url = '/';
		}

		return $asset_base_url . $asset_path . $this->manifest[ $path ];
	}

	/**
	 * Retrieve the dependencies for an asset.
	 *
	 * @param string $path Asset path.
	 * @return array
	 */
	public function dependencies( string $path ): array {
		return $this->details( $path )['dependencies'] ?? [];
	}

	/**
	 * Retrieve the version for an asset.
	 *
	 * @param string $path Asset path.
	 * @return array
	 */
	public function version( string $path ): array {
		return $this->details( $path )['version'] ?? [];
	}

	/**
	 * Retrieve an asset file from @wordpress/dependency-extraction-webpack-plugin.
	 *
	 * @param string $path Asset path.
	 * @return array
	 */
	protected function details( string $path ): array {
		// Attempt to cleanup the asset "path" to match the manifest name for the
		// asset file. The asset file is a PHP file with the asset's path suffixed
		// with `.asset.php`.
		if ( Str::contains( $path, '.' ) ) {
			$path = pathinfo( $path, PATHINFO_FILENAME );
		}

		$details_file = "{$this->manifest_directory}/{$path}.asset.php";

		if ( file_exists( $details_file ) && 0 === validate_file( $details_file ) ) {
			return require $details_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}

		return [];
	}

	/**
	 * Retrieve the path to an asset when invoked.
	 *
	 * @param string $path Asset path.
	 * @param string $manifest_directory Manifest directory, optional.
	 * @return string
	 */
	public function __invoke( string $path, ?string $manifest_directory = null ): string {
		if ( $manifest_directory ) {
			return ( new Mix( $manifest_directory ) )->path( $path );
		}

		return $this->path( $path, $manifest_directory );
	}
}
