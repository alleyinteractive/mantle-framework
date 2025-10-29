<?php
/**
 * Rest_Route_Registrar class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Closure;
use InvalidArgumentException;
use Mantle\Http\Routing\Events\Route_Matched;
use Mantle\Support\Arr;
use Mantle\Support\Pipeline;
use Mantle\Support\Str;
use WP_REST_Request;

use function Mantle\Support\Helpers\add_action;
use function Mantle\Support\Helpers\collect;

/**
 * REST API Route Registrar
 */
class Rest_Route_Registrar extends Route_Registrar {
	/**
	 * Create a new Rest_Route_Registrar instance from a base route registrar.
	 *
	 * @param Route_Registrar $base Base route registrar.
	 * @param string          $namespace Namespace for the REST API routes.
	 */
	public static function from_base( Route_Registrar $base, string $namespace ): static {
		return new static(
			router: $base->router,
			namespace: $namespace,
			attributes: $base->attributes(),
		);
	}

	/**
	 * Constructor.
	 *
	 * @param Router       $router Router instance.
	 * @param string       $namespace Route namespace.
	 * @param array<mixed> $attributes Route attributes.
	 */
	public function __construct( Router $router, protected readonly string $namespace, array $attributes = [] ) {
		parent::__construct( $router, $attributes );
	}

	/**
	 * Register the underlying route with the router.
	 *
	 * @todo Pass along namespace.
	 *
	 * @param string|string[]                  $method HTTP methods.
	 * @param string                           $uri
	 * @param Closure|array<mixed>|string|null $action Route action or arguments.
	 */
	public function register_route( string|array $method, string $uri, Closure|array|string|null $action = null ): Route {
		$method = Arr::wrap( $method );

		assert( $this->router instanceof Router, 'Router instance is not of type Router.' );

		return $this->router->add_rest_route(
			methods: $method,
			uri: $uri,
			arguments: $this->normalize_arguments( $action ?? [], $uri, $method ),
		);
	}

	/**
	 * Normalize route arguments creation of the Route object.
	 *
	 * @throws InvalidArgumentException If a callable action was not found.
	 *
	 * @param Closure|array<mixed>|string $arguments Route arguments or callback.
	 * @param string                      $uri Route URI.
	 * @param string[]                    $methods HTTP methods.
	 * @return array<mixed>
	 */
	protected function normalize_arguments( Closure|array|string $arguments, string $uri, array $methods ): array {
		$arguments = parent::normalize_arguments( $arguments, $uri, $methods );

		// Wrap the callback to provide a better integration with the router and the
		// rest of the Mantle framework.
		if ( isset( $arguments['callback'] ) ) {
			$arguments['callback'] = $this->wrap_callback(
				$arguments['callback'],
				$uri,
			);
		} else {
			throw new InvalidArgumentException(
				"No callback provided for REST API route [{$uri}].",
			);
		}

		// Ensure the namespace is forwarded to the route.
		$arguments['namespace'] = $this->namespace;

		// The REST API expects the methods to be passed in the arguments.
		$arguments['methods'] = $methods;

		// Ensure the route has a permission callback.
		if ( empty( $arguments['permission_callback'] ) ) {
			$arguments['permission_callback'] = '__return_true';
		}

		return $arguments;
	}

	/**
	 * Wrap the route callback with a valid WordPress REST response.
	 *
	 * By wrapping the callback we can provide the same type of HTTP routing that
	 * we use for web routing with the WordPress REST API. For example, we can use
	 * a controller method that has type hints of container bindings and
	 * automatically resolve them like we do for web routes.
	 *
	 * @param mixed  $callback Callback to invoke. Can be a callable function, an
	 *                         array of a controller and method, or a string
	 *                         function.
	 * @param string $route Route name.
	 */
	protected function wrap_callback( mixed $callback, string $route ): callable {
		$callback = $this->parse_route_action( $callback, $route );

		return function ( WP_REST_Request $request ) use ( $callback, $route ) {
			$middleware = $request->get_attributes()['middleware'] ?? [];

			if ( empty( $middleware ) ) {
				return rest_ensure_response( $callback( $request ) );
			}

			assert( $this->router instanceof Router, 'Router instance is not of type Router.' );

			$container = $this->router->get_container();

			$container['events']->dispatch(
				new Route_Matched(
					[
						'namespace' => $this->namespace,
						'route'     => $route,
					],
					$request,
				)
			);

			return rest_ensure_response(
				( new Pipeline( $container ) )
					->send( $request )
					->through( $this->gather_route_middleware( $middleware ) )
					->then(
						fn ( WP_REST_Request $request ) => $callback( $request ),
					)
			);
		};
	}

	/**
	 * Gather the middleware for the given route with resolved class names.
	 *
	 * @param string[] $middleware Middleware for the route.
	 * @return array<callable>
	 */
	public function gather_route_middleware( array $middleware ): array {
		assert( $this->router instanceof Router, 'Router instance is not of type Router.' );

		return collect( $middleware )
			->map(
				fn ( \Closure|string $name ) => (array) Middleware_Name_Resolver::resolve(
					$name,
					$this->router->get_middleware(),
					$this->router->get_middleware_groups()
				)
			)
			->flatten()
			->values()
			->to_array();
	}

	/**
	 * Parse a route action and return the callback.
	 *
	 * Supports closures, invokable classes, and class methods.
	 *
	 * @throws InvalidArgumentException If the action is not supported.
	 *
	 * @param mixed  $action Route action.
	 * @param string $route Route path.
	 */
	private function parse_route_action( mixed $action, string $route ): callable {
		assert( $this->router instanceof Router, 'Router instance is not of type Router.' );

		if ( is_callable( $action ) ) {
			return $action;
		}

		if ( is_string( $action ) ) {
			// Check for Controller@method callback.
			if ( Str::contains( $action, '@' ) ) {
				[ $controller, $method ] = explode( '@', $action );

				$callable = [ $this->router->get_container()->make( $controller ), $method ];

				if ( is_callable( $callable ) ) {
					return $callable;
				}
			}

			// Check for invokable classes.
			if ( class_exists( $action ) && method_exists( $action, '__invoke' ) ) {
				$callable = [ $this->router->get_container()->make( $action ), '__invoke' ];

				if ( is_callable( $callable ) ) {
					return $callable;
				}
			}
		}

		if ( is_array( $action ) && count( $action ) === 2 ) {
			[ $controller, $method ] = $action;

			$callable = [ $this->router->get_container()->make( $controller ), $method ];

			if ( is_callable( $callable ) ) {
				return $callable;
			}
		}

		throw new InvalidArgumentException( "Invalid REST API route action for [{$route}]: " . print_r( $action, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}
}
