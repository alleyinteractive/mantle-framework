<?php
/**
 * Mantle HTTP Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

declare( strict_types=1 );

use Mantle\Contracts\Http\Routing\Response_Factory;
use Mantle\Contracts\Http\View\Factory as View_Factory;
use Mantle\Http\View\View;
use Symfony\Component\Routing\Generator\UrlGenerator;

if ( ! function_exists( 'response' ) ) {
	/**
	 * Return a new response for the application.
	 *
	 * @param string $content Response content, optional.
	 * @param int    $status  Response status code, optional.
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
	 * @param  string|null $to      URL to redirect to, optional.
	 * @param  int         $status  Status code, optional.
	 * @param  array<string, string>       $headers Headers, optional.
	 * @param  bool|null   $secure  Whether the redirect should be secure, optional.
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
	 * @param  array<string>|string|null $key     Request key.
	 * @param  mixed             $default Default value.
	 * @return \Mantle\Http\Request|string|array<mixed>|null
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

if ( ! function_exists( 'view' ) ) {
	/**
	 * Return a new view or the view factory.
	 *
	 * @param string|null                 $slug View slug.
	 * @param array<string, mixed>|string $name View name or variables.
	 * @param array<string, mixed>        $variables Variables for the view, optional.
	 * @phpstan-return ($slug is null ? View_Factory : View)
	 */
	function view( ?string $slug = null, array|string|null $name = null, array $variables = [] ): View|View_Factory {
		$factory = app( View_Factory::class );

		return $slug ? $factory->make( $slug, $name, $variables ) : $factory;
	}
}

if ( ! function_exists( 'render_view' ) ) {
	/**
	 * Render a new view.
	 *
	 * @deprecated Use view() instead.
	 *
	 * @param string       $slug View slug.
	 * @param array|string $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 */
	function render_view( ...$args ): void {
		echo view( ...$args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'render_main_template' ) ) {
	/**
	 * Render the contents of the main template passed to the wrapper.
	 *
	 * The contents of the '_mantle_contents' variable are assumed to be pre-sanitized.
	 */
	function render_main_template(): void {
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
	 */
	function loop( ...$args ): string {
		return view()
			->loop( ...$args )
			->map( fn ( View $item ) => $item->render() )
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
	 */
	function render_loop( ...$args ): void {
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
	 */
	function iterate( ...$args ): string {
		return view()
		->iterate( ...$args )
		->map( fn ( View $item ) => $item->render() )
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
	 */
	function render_iterate( ...$args ): void {
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
	 * @param array<mixed>  $args Route arguments.
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
	 * @param  array<string, string>  $headers HTTP Headers
	 */
	function abort( $code, $message = '', array $headers = [] ): void {
		app()->abort( $code, $message, $headers );
	}
}

if ( ! function_exists( 'abort_if' ) ) {
	/**
	 * Throw an HttpException with the given data if the given condition is true.
	 *
	 * @param  mixed  $condition
	 * @param  int  $code
	 * @param  string  $message Response message
	 * @param  array<string, string>  $headers HTTP Headers
	 */
	function abort_if( mixed $condition, int $code, string $message = '', array $headers = [] ): void {
		if ( $condition ) {
			abort( $code, $message, $headers );
		}
	}
}

if ( ! function_exists( 'abort_unless' ) ) {
	/**
	 * Throw an HttpException with the given data unless the given condition is true.
	 *
	 * @param  mixed  $boolean
	 * @param  int  $code
	 * @param  string  $message Response message
	 * @param  array<string, string>  $headers HTTP Headers
	 */
	function abort_unless( $condition, $code, $message = '', array $headers = [] ): void {
		if ( ! $condition ) {
			abort( $code, $message, $headers );
		}
	}
}
