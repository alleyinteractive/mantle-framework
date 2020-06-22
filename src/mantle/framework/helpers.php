<?php
/**
 * Mantle Framework Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 */

use Mantle\Framework\Application;
use Mantle\Framework\Contracts\Http\Routing\Response_Factory;
use Symfony\Component\Routing\Generator\UrlGenerator;

if ( ! function_exists( 'app' ) ) {
	/**
	 * Get the available container instance.
	 *
	 * @param string|null $abstract Component name.
	 * @param array       $parameters Parameters to pass to the class.
	 * @return mixed|Mantle\Framework\Application
	 */
	function app( string $abstract = null, array $parameters = [] ) {
		if ( empty( $abstract ) ) {
			return Application::getInstance();
		}

		return Application::getInstance()->make( $abstract, $parameters );
	}
}

if ( ! function_exists( 'config' ) ) {
	/**
	 * Get a configuration value from the Configuration Repository.
	 *
	 * @param string $key Key to retrieve.
	 * @param mixed  $default Default configuration value.
	 * @return mixed
	 */
	function config( string $key = null, $default = null ) {
		if ( is_null( $key ) ) {
			return app( 'config' );
		}

		return app( 'config' )->get( $key, $default );
	}
}

/**
 * Get the base path to the application.
 *
 * @param string $path Path to append.
 * @return string
 */
function mantle_base_path( string $path = '' ): string {
	return app()->get_base_path( $path );
}

if ( ! function_exists( 'response' ) ) {
	/**
	 * Return a new response for the application.
	 *
	 * @param string $content Response content, optional.
	 * @param int    $status Response status code, optional.
	 * @param array  $headers Response headers, optional.
	 * @return Response_Factory
	 */
	function response( ...$args ) {
		$factory = app( Response_Factory::class );
		if ( empty( $args ) ) {
			return $factory;
		}

		return $factory->make( ...$args );
	}
}

if ( ! function_exists( 'route' ) ) {
	/**
	 * Generate a URL to a named route.
	 *
	 * @param string $name Route name.
	 * @param array  $args Route arguments.
	 * @return string
	 */
	function route( string $name, array $args, bool $relative = false ) {
		return app( 'url' )->generate( $name, $args, $relative ? UrlGenerator::ABSOLUTE_PATH : UrlGenerator::ABSOLUTE_URL );
	}
}
