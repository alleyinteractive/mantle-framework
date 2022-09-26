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
use Mantle\Contracts\Http\Routing\Response_Factory;
use Mantle\Contracts\Http\View\Factory as View_Factory;
use Mantle\Support\Environment;
use Mantle\Framework\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Mantle\Assets\Mix;
use Mantle\Assets\Asset_Manager;

if ( ! function_exists( 'app' ) ) {
	/**
	 * Get the available container instance.
	 *
	 * @param string|null $abstract Component name.
	 * @param array       $parameters Parameters to pass to the class.
	 * @return mixed|\Mantle\Framework\Application
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

if ( ! function_exists( 'cache' ) ) {
	/**
	 * Get / set the specified cache value.
	 *
	 * If an array is passed, we'll assume you want to put to the cache.
	 *
	 * @param  dynamic  key|key,default|data,expiration|null
	 * @return mixed|\Mantle\Framework\Cache\Cache_Manager
	 *
	 * @throws \Exception
	 */
	function cache( ...$args ) {
		if ( empty( $args ) ) {
			return app( 'cache' );
		}

		if ( isset( $args[0] ) && is_string( $args[0] ) ) {
			return app( 'cache' )->get( ...$args );
		}

		if ( ! is_array( $args[0] ) ) {
			throw new Exception(
				'When setting a value in the cache, you must pass an array of key / value pairs.'
			);
		}

		return app( 'cache' )->put( key( $args[0] ), reset( $args[0] ), $args[1] ?? null );
	}
}

if ( ! function_exists( 'remember' ) ) {
	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 *
	 * @param  string                                    $key Cache key.
	 * @param  \DateTimeInterface|\DateInterval|int|null $ttl Cache TTL.
	 * @param  \Closure                                  $callback Closure to invoke.
	 * @return mixed
	 */
	function remember( string $key, $ttl, Closure $closure ) {
		return app( 'cache' )->remember( $key, $ttl, $closure );
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
	 * @return mixed
	 */
	function environment( string $key, $default = null ) {
		return Environment::get( $key, $default );
	}
}

/**
 * Get the base path to the application.
 *
 * @param string $path Path to append.
 * @return string
 * @deprecated Use base_path().
 */
function mantle_base_path( string $path = '' ): string {
	return base_path( $path );
}

if ( ! function_exists( 'base_path' ) ) {
	/**
	 * Get the base path to the application.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	function base_path( string $path = '' ): string {
		return app()->get_base_path( $path );
	}
}

if ( ! function_exists( 'mix' ) ) {
	/**
	 * Get the path to a versioned Mix file or a Mix instance.
	 *
	 * @param  string  $path Path to the asset, optional.
	 * @param  string  $manifest_directory Path to the manifest directory, optional.
	 * @return string|Mix
	 *
	 * @throws \Exception
	 */
	function mix( string $path = null, string $manifest_directory = null ) {
		if ( ! $path ) {
			return app( Mix::class );
		}

		return app( Mix::class )( ...func_get_args() );
	}
}

if ( ! function_exists( 'app_path' ) ) {
	/**
	 * Get the application path (the app/ folder).
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	function app_path( string $path = '' ): string {
		return app()->get_app_path( $path );
	}
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

if ( ! function_exists( 'redirect' ) ) {
	/**
	 * Get an instance of the redirector.
	 *
	 * @param  string|null  $to
	 * @param  int  $status
	 * @param  array  $headers
	 * @param  bool|null  $secure
	 * @return \Mantle\Http\Routing\Redirector
	 */
	function redirect( ?string $to = null, int $status = 302, array $headers = [], ?bool $secure = null ) {
		if ( is_null( $to ) ) {
			return app( 'redirect' );
		}

		return app( 'redirect' )->to( $to, $status, $headers, $secure );
	}
}

if ( ! function_exists( 'request' ) ) {
	/**
	 * Get an instance of the current request or an input item from the request.
	 *
	 * @param  array|string|null  $key
	 * @param  mixed  $default
	 * @return \Mantle\Http\Request|string|array|null
	 */
	function request( $key = null, $default = null ) {
		if ( is_null( $key ) ) {
			return app( 'request' );
		}

		if ( is_array( $key ) ) {
			return app( 'request' )->only( $key );
		}

		$value = app( 'request' )->__get( $key );

		return is_null( $value ) ? value( $default ) : $value;
	}
}

if ( ! function_exists( 'report' ) ) {
	/**
	 * Report an exception.
	 *
	 * @param  \Throwable|string  $exception
	 * @return void
	 */
	function report( $exception ) {
		if ( is_string( $exception ) ) {
			$exception = new Exception( $exception );
		}

		app( ExceptionHandler::class )->report( $exception );
	}
}

if ( ! function_exists( 'view' ) ) {
	/**
	 * Return a new view.
	 *
	 * @param string       $slug View slug.
	 * @param array|string $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @return View|View_Factory
	 */
	function view( ...$args ) {
		$factory = app( View_Factory::class );
		if ( empty( $args ) ) {
			return $factory;
		}

		return $factory->make( ...$args );
	}
}

if ( ! function_exists( 'render_view' ) ) {
	/**
	 * Render a new view.
	 *
	 * @param string       $slug View slug.
	 * @param array|string $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @return View|View_Factory
	 */
	function render_view( ...$args ) {
		echo view( ...$args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'render_main_template' ) ) {
	/**
	 * Render the contents of the main template passed to the wrapper.
	 *
	 * The contents of the '_mantle_contents' variable are assumed to be pre-sanitized.
	 */
	function render_main_template() {
		echo mantle_get_var( '_mantle_contents' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'loop' ) ) {
	/**
	 * Loop over a collection/array of posts.
	 *
	 * @param \ArrayAccess|array $data Data to loop over.
	 * @param string           $slug View slug.
	 * @param array|string     $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @return string
	 */
	function loop( ...$args ) {
		return view()
			->loop( ...$args )
			->map
			->render()
			->implode( '' );
	}
}

if ( ! function_exists( 'render_loop' ) ) {
	/**
	 * Render the loop() function.
	 *
	 * @param \ArrayAccess|array $data Data to loop over.
	 * @param string           $slug View slug.
	 * @param array|string     $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @return string
	 */
	function render_loop( ...$args ) {
		echo loop( ...$args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'iterate' ) ) {
	/**
	 * Iterate over an array of arbitrary items, passing the index and item to a
	 * given template part.
	 *
	 * @param \ArrayAccess|array $data Data to loop over.
	 * @param string           $slug View slug.
	 * @param array|string     $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @return string
	 */
	function iterate( ...$args ) {
		return view()->iterate( ...$args )
			->map
			->render()
			->implode( '' );
	}
}

if ( ! function_exists( 'render_iterate' ) ) {
	/**
	 * Render over iterate().
	 *
	 * @param \ArrayAccess|array $data Data to loop over.
	 * @param string           $slug View slug.
	 * @param array|string     $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @return string
	 */
	function render_iterate( ...$args ) {
		echo iterate( ...$args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'mantle_get_var' ) ) {
	/**
	 * Return a new view.
	 *
	 * @param string $key Variable to get.
	 * @param mixed  $default Default value if unset.
	 * @return mixed
	 */
	function mantle_get_var( string $key, $default = null ) {
		return app( View_Factory::class )->get_var( $key, $default );
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
	function route( string $name, array $args = [], bool $relative = false ) {
		return app( 'url' )->generate( $name, $args, $relative ? UrlGenerator::ABSOLUTE_PATH : UrlGenerator::ABSOLUTE_URL );
	}
}

if ( ! function_exists( 'abort' ) ) {
	/**
	 * Throw an HttpException with the given data.
	 *
	 * @param int     $code Error code or exception.
	 * @param  string $message Response message
	 * @param  array  $headers HTTP Headers
	 */
	function abort( $code, $message = '', array $headers = [] ) {
		app()->abort( $code, $message, $headers );
	}
}

if ( ! function_exists( 'abort_if' ) ) {
	/**
	 * Throw an HttpException with the given data if the given condition is true.
	 *
	 * @param  bool  $boolean
	 * @param  int  $code
	 * @param  string  $message Response message
	 * @param  array  $headers HTTP Headers
	 */
	function abort_if( $boolean, $code, $message = '', array $headers = [] ) {
		if ( $boolean ) {
			abort( $code, $message, $headers );
		}
	}
}

if ( ! function_exists( 'abort_unless' ) ) {
	/**
	 * Throw an HttpException with the given data unless the given condition is true.
	 *
	 * @param  bool  $boolean
	 * @param  int  $code
	 * @param  string  $message Response message
	 * @param  array  $headers HTTP Headers
	 */
	function abort_unless( $boolean, $code, $message = '', array $headers = [] ) {
		if ( ! $boolean ) {
			abort( $code, $message, $headers );
		}
	}
}

if ( ! function_exists( 'storage_path' ) ) {
	/**
	 * Get the path to the storage folder.
	 *
	 * @param  string  $path Path to append.
	 * @return string
	 */
	function storage_path( string $path = '' ): string {
			return app( 'path.storage' ) . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}
}

if ( ! function_exists( 'asset' ) ) {
	/**
	 * Retrieve an instance of the Asset Manager.
	 *
	 * @return \Mantle\Assets\Asset_Manager
	 */
	function asset(): Asset_Manager {
		return app( Asset_Manager::class );
	}
}
