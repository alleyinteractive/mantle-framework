<?php
/**
 * Route_Registrar class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Mantle\Support\Arr;

/**
 * Router Registrar
 */
class Route_Registrar {
	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * The attributes to pass on to the router.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * The methods to dynamically pass through to the router.
	 *
	 * @var array
	 */
	protected $passthru = [
		'get',
		'post',
		'put',
		'patch',
		'delete',
		'options',
		'any',
	];

	/**
	 * The attributes that can be set through this class.
	 *
	 * @var array
	 */
	protected $allowed_attributes = [
		'as',
		'domain',
		'middleware',
		'name',
		'namespace',
		'prefix',
		'where',
	];

	/**
	 * The attributes that are aliased.
	 *
	 * @var array
	 */
	protected $aliases = [
		'name' => 'as',
	];

	/**
	 * Constructor.
	 *
	 * @param Router $router Router instance.
	 */
	public function __construct( Router $router ) {
		$this->router = $router;
	}

	/**
	 * Set the value for a given attribute.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return $this
	 *
	 * @throws InvalidArgumentException Thrown on unknown attribute.
	 */
	public function attribute( $key, $value ) {
		if ( ! in_array( $key, $this->allowed_attributes ) ) {
			throw new InvalidArgumentException( "Attribute [{$key}] does not exist." );
		}

		$this->attributes[ Arr::get( $this->aliases, $key, $key ) ] = $value;

		return $this;
	}

	/**
	 * Create a route group with shared attributes.
	 *
	 * @param  \Closure|string $callback
	 * @return void
	 */
	public function group( $callback ) {
		$this->router->group( $this->attributes, $callback );
	}

	/**
	 * Register a new route with the given verbs.
	 *
	 * @param  array|string               $methods
	 * @param  string                     $uri
	 * @param  \Closure|array|string|null $action
	 * @return \Illuminate\Routing\Route
	 */
	public function match( $methods, $uri, $action = null ) {
		return $this->router->match( $methods, $uri, $this->compile_action( $action ) );
	}

	/**
	 * Register a new route with the router.
	 *
	 * @param  string                     $method
	 * @param  string                     $uri
	 * @param  \Closure|array|string|null $action
	 * @return \Illuminate\Routing\Route
	 */
	protected function register_route( $method, $uri, $action = null ) {
		if ( ! is_array( $action ) ) {
			$action = array_merge( $this->attributes, $action ? [ 'callback' => $action ] : [] );
		}

		return $this->router->{$method}( $uri, $this->compile_action( $action ) );
	}

	/**
	 * Compile the action into an array including the attributes.
	 *
	 * @param  \Closure|array|string|null $action
	 * @return array
	 */
	protected function compile_action( $action ) {
		if ( is_null( $action ) ) {
			return $this->attributes;
		}

		if ( is_string( $action ) || $action instanceof Closure ) {
			$action = [ 'callback' => $action ];
		}

		return array_merge( $this->attributes, $action );
	}

	/**
	 * Pass the REST API method back to the REST API registrar.
	 *
	 * @param string          $namespace Route namespace.
	 * @param \Closure|string $route Route name or callback to register more routes.
	 * @param array           $args Route arguments.
	 * @return Rest_Route_Registrar
	 */
	public function rest_api( string $namespace, $route, $args = [] ): Rest_Route_Registrar {
		if ( $args instanceof Closure ) {
			$args = [
				'callback' => $args,
			];
		}

		if ( is_array( $args ) ) {
			$args = array_merge( $this->attributes, $args );
		}

		return $this->router->rest_api( $namespace, $route, $args );
	}

	/**
	 * Dynamically handle calls into the route registrar.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 * @return \Illuminate\Routing\Route|$this
	 *
	 * @throws BadMethodCallException Thrown on missing method.
	 */
	public function __call( $method, $parameters ) {
		if ( in_array( $method, $this->passthru ) ) {
			return $this->register_route( $method, ...$parameters );
		}

		if ( in_array( $method, $this->allowed_attributes ) ) {
			if ( 'middleware' === $method ) {
				return $this->attribute( $method, is_array( $parameters[0] ) ? $parameters[0] : $parameters );
			}

			return $this->attribute( $method, $parameters[0] );
		}

		throw new BadMethodCallException(
			sprintf(
				'Method %s::%s does not exist.',
				static::class,
				$method
			)
		);
	}
}
