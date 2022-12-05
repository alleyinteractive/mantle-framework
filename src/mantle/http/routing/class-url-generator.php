<?php
/**
 * Url_Generator class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Mantle\Contracts\Http\Routing\Url_Generator as Generator_Contract;
use Mantle\Http\Request;
use Mantle\Support\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * URL Generator
 *
 * Generates URLs to routes, paths, etc. on th e site.
 */
class Url_Generator extends UrlGenerator implements Generator_Contract {
	/**
	 * The request instance.
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * Root URL for the application.
	 *
	 * @var string
	 */
	protected $root_url;

	/**
	 * A cached copy of the URL scheme for the current request.
	 *
	 * @var string|null
	 */
	protected $cached_scheme;

	/**
	 * The forced scheme for URLs.
	 *
	 * @var string
	 */
	protected $force_scheme;

	/**
	 * Constructor.
	 *
	 * @param string               $root_url Root URL.
	 * @param RouteCollection      $routes Route collection.
	 * @param Request              $request Request object.
	 * @param LoggerInterface|null $logger Logger instance.
	 */
	public function __construct( string $root_url, RouteCollection $routes, Request $request, LoggerInterface $logger = null ) {
		$this->root_url = $root_url;
		$this->routes   = $routes;
		$this->logger   = $logger;

		$this->set_request( $request );
	}

	/**
	 * Set the request object.
	 *
	 * @param Request $request Request object to set.
	 * @return static
	 */
	public function set_request( Request $request ) {
		$this->request = $request;
		$this->context = ( new RequestContext() )->fromRequest( $request );

		if ( ! $this->context->hasParameter( '_locale' ) ) {
			$this->context->setParameter( '_locale', 'en' );
		}

		return $this;
	}

	/**
	 * Get the request object.
	 *
	 * @return Request
	 */
	public function get_request(): Request {
		return $this->request;
	}

	/**
	 * Get the current URL for the request.
	 *
	 * @return string
	 */
	public function current() {
		return $this->to( $this->request->getPathInfo() );
	}

	/**
	 * Get the URL for the previous request.
	 *
	 * @param string $fallback Fallback value, optional.
	 * @return string
	 */
	public function previous( string $fallback = null ): string {
		return $this->to(
			$this->request->headers->get( 'referer', $fallback ?? '/' )
		);
	}

	/**
	 * Generate a URL to a specific path.
	 *
	 * @param string $path URL Path.
	 * @param array  $extra Extra parameters.
	 * @param bool   $secure Flag if should be forced to be secure.
	 * @return string
	 */
	public function to( string $path, array $extra = [], bool $secure = null ) {
		// First we will check if the URL is already a valid URL. If it is we will not
		// try to generate a new one but will simply return the URL as is, which is
		// convenient since developers do not always have to check if it's valid.
		if ( $this->is_valid_url( $path ) ) {
			return $path;
		}

		$tail = implode(
			'/',
			array_map(
				'rawurlencode',
				(array) $this->format_parameters( $extra )
			)
		);

		// Once we have the scheme we will compile the "tail" by collapsing the values
		// into a single string delimited by slashes. This just makes it convenient
		// for passing the array of parameters to this URL as a list of segments.
		$root = $this->get_root_url();

		[ $path, $query ] = $this->extractQueryString( $path );

		return $this->format(
			$root,
			'/' . trailingslashit( trim( $path, '/' ) ),
		) . $query;
	}

	/**
	 * Format the array of URL parameters.
	 *
	 * @param  mixed|array $parameters
	 * @return array
	 */
	public function format_parameters( $parameters ): array {
		return Arr::wrap( $parameters );
	}

	/**
	 * Get the default scheme for a raw URL.
	 *
	 * @param  bool|null $secure Flag if should be secure.
	 * @return string
	 */
	public function format_scheme( $secure = null ): string {
		if ( ! is_null( $secure ) ) {
			return $secure ? 'https://' : 'http://';
		}

		if ( is_null( $this->cached_scheme ) ) {
			$this->cached_scheme = $this->force_scheme ?: $this->context->getScheme() . '://';
		}

		return $this->cached_scheme;
	}

	/**
	 * Extract the query string from the given path.
	 *
	 * @param  string $path URL Path.
	 * @return array
	 */
	protected function extractQueryString( $path ) {
		$query_position = strpos( $path, '?' );
		if ( false !== $query_position ) {
			return [
				substr( $path, 0, $query_position ),
				substr( $path, $query_position ),
			];
		}

		return [ $path, '' ];
	}

	/**
	 * Get the root URL.
	 *
	 * @return string
	 */
	public function get_root_url(): string {
		return $this->root_url;
	}

	/**
	 * Set the root URL.
	 *
	 * @param string $url Root URL to set.
	 * @return static
	 */
	public function root_url( string $url ) {
		$this->root_url = $url;

		$this->context->setHost( wp_parse_url( $url, PHP_URL_HOST ) );

		return $this;
	}

	/**
	 * Format the given URL segments into a single URL.
	 *
	 * @param  string $root URL root.
	 * @param  string $path URL path.
	 * @return string
	 */
	public function format( $root, $path ) {
		$path = '/' . trim( $path, '/' );
		return trailingslashit( trim( $root . $path, '/' ) );
	}

	/**
	 * Determine if the given path is a valid URL.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function is_valid_url( $path ): bool {
		if ( ! preg_match( '~^(#|//|https?://|(mailto|tel|sms):)~', $path ) ) {
			return filter_var( $path, FILTER_VALIDATE_URL ) !== false;
		}

		return true;
	}

	/**
	 * Force the scheme for URLs.
	 *
	 * @param  string $scheme
	 * @return void
	 */
	public function force_scheme( $scheme ) {
		$this->cached_scheme = null;

		$this->force_scheme = $scheme . '://';
	}

	/**
	 * Set the routes in the generator.
	 *
	 * @param RouteCollection $routes Route collection.
	 * @return static
	 */
	public function set_routes( RouteCollection $routes ) {
		$this->routes = $routes;
		return $this;
	}
}
