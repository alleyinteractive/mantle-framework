<?php
/**
 * Asset_Map class file
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use InvalidArgumentException;
use Mantle\Assets\Exception\Asset_Map_Not_Found;

/**
 * Asset Map
 *
 * Manage and enqueue assets from the asset map.
 */
class Asset_Map {
	/**
	 * Storage of all parsed asset maps.
	 *
	 * @var array
	 */
	protected static array $maps = [];

	/**
	 * Current asset map being referenced.
	 *
	 * @var string
	 */
	protected $map;

	/**
	 * Read a map from a specific path.
	 *
	 * @throws Exception\Asset_Map_Not_Found Thrown when asset map not found and in debug mode.
	 *
	 * @param string $path Path to read.
	 * @param string $name  Map to read, optional.
	 * @return static|null
	 */
	public static function create( string $path, string $name = null ) {
		$path = realpath( $path );

		$name ??= $path;

		if ( isset( static::$maps[ $name ] ) ) {
			return new static( $name );
		}

		if ( empty( $path ) || ! is_file( $path ) ) {
			$exception = new Exception\Asset_Map_Not_Found( "The asset map file does not exist: {$path}" );

			if ( ! config( 'app.debug' ) ) {
				report( $exception );

				return null;
			} else {
				throw $exception;
			}
		}

		static::$maps[ $name ] = json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return new static( $name );
	}

	/**
	 * Read the default asset map for the site and return an Asset Map instance.
	 *
	 * @return static
	 */
	public static function default_map() {
		return static::create( base_path( 'build/assetMap.json' ) );
	}

	/**
	 * Constructor.
	 *
	 * @param string $map Asset map to read, optional.
	 */
	public function __construct( string $map = 'default' ) {
		$this->map = $map;
	}

	/**
	 * Retrieve a property for a given asset.
	 *
	 * @param string $asset Entry point and asset type separated by a '.'.
	 * @param string $prop  The property to get from the entry object.
	 *
	 * @return array|string|null The asset property based on entry and type.
	 */
	public function get( string $asset, string $prop = null ) {
		[ $asset, $type ] = explode( '.', $asset, 2 );

		$entry = static::$maps[ $this->map ][ $asset ][ $type ] ?? null;

		if ( ! $prop ) {
			return $entry;
		}

		return $entry[ $prop ] ?? null;
	}

	/**
	 * Retrieve a URL for a given property.
	 *
	 * @param string $asset Entry point and asset type separated by a '.'.
	 * @return string|null
	 */
	public function path( string $asset ): ?string {
		$path = $this->get( $asset, 'path' );

		if ( empty( $path ) ) {
			return null;
		}

		$base_path = trailingslashit(
			config( 'assets.url', plugin_dir_url( base_path() . '/mantle.php' ) )
			. config( 'assets.path', 'build' )
		);

		return "{$base_path}{$path}";
	}

	/**
	 * Retrieve the directory path for a given asset.
	 *
	 * @param string $asset Entry point and asset type separated by a '.'.
	 * @return string
	 */
	public function dir_path( string $asset ): ?string {
		$path = $this->get( $asset, 'path' );

		if ( empty( $path ) ) {
			return null;
		}

		return trailingslashit( base_path( config( 'assets.path', 'build' ) ) ) . $path;
	}

	/**
	 * Retrieve the hash for a given asset.
	 *
	 * @param string $asset Entry point and asset type separated by a '.'.
	 * @return string|null
	 */
	public function hash( string $asset ): ?string {
		return $this->get( $asset, 'hash' ) ?? static::$maps[ $this->map ]['hash'] ?? null;
	}

	/**
	 * Retrieve the dependencies for a given asset.
	 *
	 * @param string $asset Entry point and asset type separated by a '.'.
	 * @return array
	 */
	public function dependencies( string $asset ): array {
		// Strip the asset type from the asset name if passed.
		if ( false !== strpos( $asset, '.' ) ) {
			[ $asset ] = explode( '.', $asset, 2 );
		}

		$dependency_file = $this->dir_path( "{$asset}.php" );

		// Ensure the filepath is valid.
		if ( empty( $dependency_file ) || ! file_exists( $dependency_file ) || 0 !== validate_file( $dependency_file ) ) {
			return [];
		}

		// Try to load the dependencies.
		$dependencies = require $dependency_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

		if ( empty( $dependencies['dependencies'] ) || ! is_array( $dependencies['dependencies'] ) ) {
			return [];
		}

		return $dependencies['dependencies'];
	}

	/**
	 * Enqueue an asset from the asset map.
	 *
	 * @throws InvalidArgumentException Thrown when the asset type is invalid (expects js/css).
	 *
	 * @param string $asset Asset to enqueue.
	 * @return Asset|null
	 */
	public function enqueue( string $asset ): ?Asset {
		$path = $this->path( $asset );

		if ( empty( $path ) ) {
			return null;
		}

		[ $name, $type ] = explode( '.', $asset, 2 );

		if ( empty( $type ) ) {
			throw new InvalidArgumentException( "Invalid asset type: {$type} ({$asset})" );
		}

		// todo: convert to non-match.
		$type = match ($type) {
			'js' => 'script',
			'css' => 'style',
			default => $type,
		};

		return new Asset(
			$type,
			$name,
			$this->path( $asset ),
			$this->dependencies( $asset ),
		);
	}
}
