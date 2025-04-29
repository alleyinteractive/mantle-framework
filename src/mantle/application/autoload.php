<?php
/**
 * Mantle Framework Application Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

declare( strict_types=1 );

use Mantle\Application\Application;
use Mantle\Support\Environment;
use Mantle\Support\Mixed_Data;

use function Mantle\Support\Helpers\mixed;

if ( ! function_exists( 'app' ) ) {
	/**
	 * Get the available container instance.
	 *
	 * @param string|null $abstract Abstract to resolve.
	 * @param array<mixed> $parameters Parameters.
	 * @return mixed|Application
	 * @phpstan-return ($abstract is null ? Application : mixed)
	 */
	function app( ?string $abstract = null, array $parameters = [] ) {
		if ( is_null( $abstract ) ) {
			return Application::get_instance();
		}

		return Application::get_instance()->make( $abstract, $parameters );
	}
}

if ( ! function_exists( 'environment' ) ) {
	/**
	 * Gets the value of an environment variable.
	 *
	 * @see Mantle\Support\Environment
	 *
	 * @param  string $key Environment variable key.
	 * @param  mixed  $default Default value.
	 */
	function environment( string $key, $default = null ): mixed {
		return Environment::get( $key, $default );
	}
}

if ( ! function_exists( 'environment_mixed' ) ) {
	/**
	 * Gets the value of an environment variable as a Mixed Data object.
	 *
	 * @param  string $key Environment variable key.
	 * @param  mixed  $default Default value.
	 */
	function environment_mixed( string $key, $default = null ): Mixed_Data {
		return mixed( environment( $key, $default ) );
	}
}

if ( ! function_exists( 'base_path' ) ) {
	/**
	 * Get the base path to the application.
	 *
	 * @param string $path Path to append.
	 */
	function base_path( string $path = '' ): string {
		return app()->get_base_path( $path );
	}
}

if ( ! function_exists( 'app_path' ) ) {
	/**
	 * Get the application path (the app/ folder).
	 *
	 * @param string $path Path to append.
	 */
	function app_path( string $path = '' ): string {
		return app()->get_app_path( $path );
	}
}

if ( ! function_exists( 'storage_path' ) ) {
	/**
	 * Get the path to the storage folder.
	 *
	 * @param  string  $path Path to append.
	 */
	function storage_path( string $path = '' ): string {
		return app()->get_storage_path( $path );
	}
}

if ( ! function_exists( 'now' ) ) {
	/**
	 * Create a new Carbon instance for the current time.
	 *
	 * @todo Allow this to be faked and mocked during testing.
	 *
	 * @param DateTimeZone|string|null $tz Timezone.
	 */
	function now( \DateTimeZone|string|null $tz = null ): Carbon\Carbon {
		if ( ! $tz ) {
			$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		}

		return Carbon\Carbon::now( $tz );
	}
}
