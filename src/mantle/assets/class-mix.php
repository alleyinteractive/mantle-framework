<?php
/**
 * Mix class file.
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use Mantle\Assets\Exception\Mix_File_Not_Found;
use Mantle\Support\Str;

class Mix {
	/**
	 * Get the path to a versioned Mix file.
	 *
	 * @param  string  $path Mix file path.
	 * @param  string  $manifest_directory Manifest directory, defaults to application.
	 * @return string
	 *
	 * @throws Mix_File_Not_Found
	 */
	public function __invoke( string $path, ?string $manifest_directory = null ): string {
		static $manifests = [];

		if ( ! Str::starts_with( $path, '/' ) ) {
			$path = "/{$path}";
		}

		if ( $manifest_directory && ! Str::starts_with( $manifest_directory, '/' ) ) {
			$manifest_directory = "/{$manifest_directory}";
		} else {
			// Set the default manifest directory.
			$manifest_directory = config( 'assets.path', 'build' );
		}

		// Pass directly to HMR.
		if ( is_file( base_path( $manifest_directory . '/hot' ) ) ) {
			$url = rtrim( file_get_contents( base_path( $manifest_directory . '/hot' ) ) );

			if ( Str::starts_with( $url, [ 'http://', 'https://' ] ) ) {
				return Str::after( $url, ':' ) . $path;
			}

			return "//localhost:8080/{$path}";
		}

		// Combine t
		$manifest_path = base_path( "{$manifest_directory}/mix-manifest.json" );

		if ( ! isset( $manifests[ $manifest_path ] ) ) {
			if (! is_file( $manifest_path ) ) {
				throw new Mix_File_Not_Found( 'The Mix manifest does not exist.' );
			}

			$manifests[ $manifest_path ] = json_decode( file_get_contents( $manifest_path ), true );
		}

		$manifest = $manifests[ $manifest_path ];

		if ( ! isset( $manifest[ $path ] ) ) {
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

		return $asset_base_url . $manifest_directory . $manifest[ $path ];
	}
}
