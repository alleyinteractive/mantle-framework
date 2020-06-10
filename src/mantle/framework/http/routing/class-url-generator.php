<?php
namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Contracts\Http\Routing\Url_Generator as Generator_Contract;
use Mantle\Framework\Support\Arr;
use Mantle\Framework\Support\Str;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Url_Generator extends UrlGenerator implements Generator_Contract {
	 /**
	 * A cached copy of the URL root for the current request.
	 *
	 * @var string|null
	 */
	protected $cachedRoot;

	/**
	 * A cached copy of the URL scheme for the current request.
	 *
	 * @var string|null
	 */
	protected $cachedScheme;

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
		$root = $this->formatRoot( $this->formatScheme( $secure ) );

		[$path, $query] = $this->extractQueryString( $path );

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
			// 	$parameters[ $key ] = $parameter->getRouteKey();
			// }
		}

		return $parameters;
	}

	/**
	 * Get the default scheme for a raw URL.
	 *
	 * @param  bool|null  $secure
	 * @return string
	 */
	public function formatScheme($secure = null)
	{
			if (! is_null($secure)) {
					return $secure ? 'https://' : 'http://';
			}

			if (is_null($this->cachedScheme)) {
					$this->cachedScheme = $this->forceScheme ?: $this->request->getScheme().'://';
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
	 * Get the base URL for the request.
	 *
	 * @param  string      $scheme
	 * @param  string|null $root
	 * @return string
	 */
	public function formatRoot( $scheme, $root = null ) {
		if ( is_null( $root ) ) {
			if ( is_null( $this->cachedRoot ) ) {
				$this->cachedRoot = $this->forcedRoot ?: $this->request->root();
			}

			$root = $this->cachedRoot;
		}

		$start = Str::starts_with( $root, 'http://' ) ? 'http://' : 'https://';

		return preg_replace( '~' . $start . '~', $scheme, $root, 1 );
	}

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
}
