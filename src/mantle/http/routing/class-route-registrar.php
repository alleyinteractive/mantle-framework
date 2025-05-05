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
use Mantle\Contracts\Http\Routing\Route_Registrar as Registrar_Contract;
use Mantle\Support\Arr;
use Mantle\Support\Str;

/**
 * Router Registrar
 *
 * Provides a fluent interface for registering routes with the router. This
 * class will be called to setup attributes such as middleware, prefix, etc.
 * that should be shared across multiple routes that are registered in a group.
 *
 * @todo Forward all routing through a route registrar for consistency.
 *
 * @method \Mantle\Http\Routing\Route_Registrar as(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar domain(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar middleware(array<string>|string|null $middleware)
 * @method \Mantle\Http\Routing\Route_Registrar name(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar namespace(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar prefix(string $value)
 * @method \Mantle\Http\Routing\Route_Registrar where(array<mixed> $where)
 */
class Route_Registrar implements Registrar_Contract {
	/**
	 * The attributes to pass on to the router.
	 *
	 * @var array<mixed>
	 */
	protected $attributes = [];

	/**
	 * The methods to dynamically pass through to the router.
	 *
	 * @var array<mixed>
	 */
	public const HTTP_METHODS = [
		'GET',
		'POST',
		'PUT',
		'PATCH',
		'DELETE',
		'OPTIONS',
	];

	/**
	 * The attributes that can be set through this class.
	 *
	 * @var array<mixed>
	 */
	protected array $allowed_attributes = [
		'as_prefix',
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
	 * @var array<mixed>
	 */
	protected array $aliases = [
		'as'   => 'as_prefix',
		'name' => 'as_prefix',
	];

	/**
	 * Constructor.
	 *
	 * @param Router $router Router instance.
	 */
	public function __construct( protected ?Router $router ) {}

	/**
	 * Set the value for a given attribute.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 *
	 * @throws InvalidArgumentException Thrown on unknown attribute.
	 */
	public function attribute( string $key, mixed $value ): static {
		if ( ! in_array( $key, $this->allowed_attributes, true ) ) {
			throw new InvalidArgumentException( "Attribute [{$key}] does not exist." );
		}

		$this->attributes[ Arr::get( $this->aliases, $key, $key ) ] = $value;

		return $this;
	}

	/**
	 * Create a route group with shared attributes.
	 *
	 * @param  \Closure|string $callback
	 */
	public function group( callable|string $callback ): static {
		$this->router->group( $this->attributes, $callback );

		return $this;
	}

	/**
	 * Register a new route with the router.
	 *
	 * @param  string|string[]                            $method
	 * @param  string                            $uri
	 * @param  \Closure|array<mixed>|string|null $action
	 */
	public function register_route( string|array $method, string $uri, Closure|array|string $action = null ): Route {
		$method = match ( true ) {
			is_array( $method ) => array_map( 'strtoupper', $method ),
			'any' === $method => self::HTTP_METHODS,
			default => strtoupper( $method ),
		};

		return $this->router->add_route( $method, $uri, $this->normalize_arguments( $action, $uri, $method ) );
	}

	/**
	 * Normalize the arguments that are passed to the newly created route.
	 *
	 * @param Closure|array<mixed>|string $arguments Route arguments or callback.
	 * @param string $uri Route URI.
	 * @param string[] $methods HTTP methods.
	 * @return array<mixed>
	 */
	protected function normalize_arguments( Closure|array|string $arguments, string $uri, array $methods ): array {
		if ( ! is_array( $arguments ) ) {
			$arguments = [
				'callback' => $arguments,
			];
		}

		$arguments = array_merge( $this->attributes, $arguments );

		// Translate a class@method callback into a "callable".
		if ( is_string( $arguments['callback'] ) && str_contains( $arguments['callback'], '@' ) ) {
			$arguments['callback'] = Str::parse_callback( $arguments['callback'] );
		}

		return $arguments;
	}

	/**
	 * Compile the action into an array including the attributes.
	 *
	 * @param  \Closure|array<mixed>|string|null $action
	 * @return array<mixed>
	 */
	// protected function compile_action( Closure|array|string|null $action ): array {
	// 	if ( is_null( $action ) ) {
	// 		return $this->attributes;
	// 	}

	// 	if ( is_string( $action ) || $action instanceof Closure ) {
	// 		$action = [ 'callback' => $action ];
	// 	}

	// 	return array_merge( $this->attributes, $action );
	// }

	/**
	 * Pass the REST API method back to the REST API registrar.
	 *
	 * @param string               $namespace Route namespace.
	 * @param Closure|string       $route Route name or callback to register more routes.
	 * @param array<mixed>|Closure $args Route arguments.
	 */
	// public function rest_api( string $namespace, Closure|string $route, array|Closure $args = [] ): Rest_Route_Registrar {
	// 	if ( $args instanceof Closure ) {
	// 		$args = [
	// 			'callback' => $args,
	// 		];
	// 	}

	// 	if ( is_array( $args ) ) { // @phpstan-ignore-line function.alreadyNarrowedType
	// 		$args = array_merge( $this->attributes, $args );
	// 	}

	// 	return $this->router->rest_api( $namespace, $route, $args );
	// }

	/**
	 * Dynamically handle calls into the route registrar.
	 *
	 * @param  string       $method
	 * @param  array<mixed> $parameters
	 * @return \Mantle\Http\Routing\Route|static
	 *
	 * @throws BadMethodCallException Thrown on missing method.
	 */
	public function __call( string $method, array $parameters ) {
		if ( 'any' === $method || in_array( strtoupper( $method ), self::HTTP_METHODS, true ) ) {
			return $this->register_route( $method, ...$parameters );
		}

		if ( in_array( $method, $this->allowed_attributes, true ) ) {
			if ( 'middleware' === $method ) {
				// @phpstan-ignore return.type
				return $this->attribute( $method, is_array( $parameters[0] ) ? $parameters[0] : $parameters )->attributes;
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
