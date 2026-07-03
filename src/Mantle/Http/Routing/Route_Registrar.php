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
	 * The methods to dynamically pass through to the router.
	 *
	 * @todo Convert to an enum.
	 *
	 * @var string[]
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
	 * @var string[]
	 */
	public const ALLOWED_ATTRIBUTES = [
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
	 * @param Router       $router Router instance.
	 * @param array<mixed> $attributes The attributes to pass on to the router.
	 */
	public function __construct( public readonly ?Router $router, protected array $attributes = [] ) {}

	/**
	 * Retrieve the registrar's attributes.
	 *
	 * @return array<mixed>
	 */
	public function attributes(): array {
		return $this->attributes;
	}

	/**
	 * Set the value for a given attribute.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 *
	 * @throws InvalidArgumentException Thrown on unknown attribute.
	 */
	public function attribute( string $key, mixed $value ): static {
		if ( ! in_array( $key, static::ALLOWED_ATTRIBUTES, true ) ) {
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
		assert( $this->router instanceof Router, 'Router instance is required to create route groups.' );

		$this->router->group( $this->attributes, $callback );

		return $this;
	}

	/**
	 * Register a new route with the router.
	 *
	 * @param  string|string[]                   $method
	 * @param  string                            $uri
	 * @param  \Closure|array<mixed>|string|null $action
	 */
	public function register_route( string|array $method, string $uri, Closure|array|string|null $action = null ): Route {
		assert( $this->router instanceof Router, 'Router instance is required to create route groups.' );

		$method = match ( true ) {
			is_array( $method ) => array_map( strtoupper( ... ), $method ),
			'any' === $method => self::HTTP_METHODS,
			default => [ strtoupper( $method ) ],
		};

		return $this->router->add_route( $method, $uri, $this->normalize_arguments( $action ?? [], $uri, $method ) );
	}

	/**
	 * Normalize the arguments that are passed to the newly created route.
	 *
	 * @param Closure|array<mixed>|string $arguments Route arguments or callback.
	 * @param string                      $uri Route URI.
	 * @param string[]                    $methods HTTP methods.
	 * @return array<mixed>
	 */
	protected function normalize_arguments( Closure|array|string $arguments, string $uri, array $methods ): array {
		// If the arguments are not an array list (array only with numeric keys) we
		// will assume it is the callback in some form and wrap it in an array.
		if ( ! is_array( $arguments ) || array_is_list( $arguments ) ) {
			$arguments = [
				'callback' => $arguments,
			];
		}

		$arguments = array_merge( $this->attributes, $arguments );

		// Translate a class@method callback into a "callable".
		if ( isset( $arguments['callback'] ) && is_string( $arguments['callback'] ) && str_contains( $arguments['callback'], '@' ) ) {
			$arguments['callback'] = Str::parse_callback( $arguments['callback'] );
		}

		return $arguments;
	}

	/**
	 * Register a REST API route.
	 *
	 * @todo How can we condense this back into a single router method? Right now this is
	 *       duplicated from the router.
	 *
	 * @param string                       $namespace        Namespace for the REST API route.
	 * @param callable|string              $callback_or_uri  Callback that will be invoked to register
	 *                                                       routes or a string route path.
	 * @param callable|array<mixed>|string $args             Callback for the route if $callback or route arguments.
	 */
	public function rest_api( string $namespace, callable|string $callback_or_uri, callable|array|string $args = [] ): ?Route {
		$namespace = trim( $namespace, '/' );

		assert( $this->router instanceof Router, 'Router instance is required to create route groups.' );

		$previous_registrar = $this->router->registrar;

		$this->router->registrar = Rest_Route_Registrar::from_base( $this, $namespace );

		if ( is_callable( $callback_or_uri ) ) {
			$callback_or_uri();

			$this->router->registrar = $previous_registrar;

			return null;
		}

		// If a third argument is a callable we will assume it is the action and the
		// second argument is the route.
		if ( is_callable( $args ) ) {
			$route = $this->router->registrar->register_route(
				method: [ 'GET', 'HEAD' ],
				uri: $callback_or_uri,
				action: $args, // @phpstan-ignore-line argument.type
			);

			$this->router->registrar = $previous_registrar;

			return $route;
		}

		assert( is_array( $args ), 'Route arguments must be an array if not a callable.' );

		$args['methods'] = isset( $args['methods'] )
			? Arr::wrap( $args['methods'] )
			: [ 'GET', 'HEAD' ];

		$route = $this->router->registrar->register_route(
			method: $args['methods'],
			uri: $callback_or_uri,
			action: $args,
		);

		$this->router->registrar = $previous_registrar;

		return $route;
	}

	/**
	 * Dynamically handle calls into the route registrar.
	 *
	 * @param  string       $method
	 * @param  array<mixed> $parameters
	 *
	 * @throws BadMethodCallException Thrown on missing method.
	 */
	public function __call( string $method, array $parameters ): Route|static {
		if ( 'any' === $method || in_array( strtoupper( $method ), self::HTTP_METHODS, true ) ) {
			return $this->register_route( $method, ...$parameters );
		}

		if ( ! in_array( $method, static::ALLOWED_ATTRIBUTES, true ) ) {
			throw new BadMethodCallException(
				sprintf(
					'Method %s::%s does not exist.',
					static::class,
					$method
				)
			);
		}

		// Middleware should be merged with the existing middleware.
		if ( 'middleware' === $method ) {
			$middleware = $this->attributes['middleware'] ?? [];

			$middleware = array_merge(
				$middleware,
				is_array( $parameters[0] ) ? $parameters[0] : [ $parameters[0] ]
			);

			return $this->attribute( 'middleware', $middleware );
		}

		return $this->attribute( $method, $parameters[0] );
	}
}
