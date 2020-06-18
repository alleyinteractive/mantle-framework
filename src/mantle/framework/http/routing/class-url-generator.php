<?php
namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Http\Routing\Url_Generator as Generator_Contract;
use Mantle\Framework\Http\Request;
use Mantle\Framework\Support\Arr;
use Mantle\Framework\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

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
	protected $cachedScheme;

	/**
	 * The forced scheme for URLs.
	 *
	 * @var string
	 */
	protected $forceScheme;

	/**
	 * Constructor.
	 *
	 * @param RouteCollection $routes Route collection.
	 * @param Request         $request Request object.
	 * @param LoggerInterface $logger Logger interface.
	 */
	public function __construct( string $root_url = '', RouteCollection $routes, Request $request, LoggerInterface $logger = null ) {
		$this->root_url = $root_url;
		$this->routes   = $routes;

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

	public function to( string $path, array $extra = [], bool $secure = null ) {
		if ( $this->isValidUrl( $path ) ) {
			return $path;
		}

		$tail = implode(
			'/',
			array_map(
				'rawurlencode',
				(array) $this->formatParameters( $extra )
			)
		);

		// Once we have the scheme we will compile the "tail" by collapsing the values
		// into a single string delimited by slashes. This just makes it convenient
		// for passing the array of parameters to this URL as a list of segments.
		$root = $this->get_root_url();

		[ $path, $query ] = $this->extractQueryString( $path );

		return $this->format(
			$root,
			'/' . trim( $path . '/' . $tail, '/' )
		) . $query;
	}

	/**
	 * Format the array of URL parameters.
	 *
	 * @param  mixed|array $parameters
	 * @return array
	 */
	public function formatParameters( $parameters ) {
		$parameters = Arr::wrap( $parameters );

		foreach ( $parameters as $key => $parameter ) {
			// if ( $parameter instanceof UrlRoutable ) {
			// $parameters[ $key ] = $parameter->getRouteKey();
			// }
		}

		return $parameters;
	}

	/**
	 * Get the default scheme for a raw URL.
	 *
	 * @param  bool|null $secure
	 * @return string
	 */
	public function formatScheme( $secure = null ) {
		if ( ! is_null( $secure ) ) {
				return $secure ? 'https://' : 'http://';
		}

		if ( is_null( $this->cachedScheme ) ) {
				$this->cachedScheme = $this->forceScheme ?: $this->context->getScheme() . '://';
		}

			return $this->cachedScheme;
	}

	/**
	 * Extract the query string from the given path.
	 *
	 * @param  string $path
	 * @return array
	 */
	protected function extractQueryString( $path ) {
		if ( ( $queryPosition = strpos( $path, '?' ) ) !== false ) {
			return [
				substr( $path, 0, $queryPosition ),
				substr( $path, $queryPosition ),
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
	 * Get the base URL for the request.
	 *
	 * @param  string      $scheme
	 * @param  string|null $root
	 * @return string
	 */
	// public function formatRoot( $scheme, $root = null ) {
	// 	if ( is_null( $root ) ) {
	// 		$this->cachedRoot = $this->context->getBaseUrl();
	// 		// if ( is_null( $this->cachedRoot ) ) {
	// 		// $this->cachedRoot = $this->forcedRoot ?: $this->context->getBaseUrl();
	// 		// }

	// 		$root = $this->cachedRoot;
	// 	}

	// 	$start = Str::starts_with( $root, 'http://' ) ? 'http://' : 'https://';

	// 	return preg_replace( '~' . $start . '~', $scheme, $root, 1 );
	// }

	/**
	 * Format the given URL segments into a single URL.
	 *
	 * @param  string                         $root
	 * @param  string                         $path
	 * @param  \Illuminate\Routing\Route|null $route
	 * @return string
	 */
	public function format( $root, $path, $route = null ) {
		$path = '/' . trim( $path, '/' );
		return trim( $root . $path, '/' );
	}

	/**
	 * Determine if the given path is a valid URL.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function isValidUrl( $path ) {
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
	public function forceScheme( $scheme ) {
			$this->cachedScheme = null;

			$this->forceScheme = $scheme . '://';
	}
}
