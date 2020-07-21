<?php
/**
 * Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use Closure;
use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Contracts\Http\Routing\Router as Router_Contract;
use Mantle\Framework\Http\Http_Exception;
use Mantle\Framework\Http\Request;
use Mantle\Framework\Pipeline;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Response as Symfony_Response;

use function Mantle\Framework\Helpers\collect;

/**
 * Router
 *
 * Allow registration of routes to the application.
 */
class Router implements Router_Contract {
	use Concerns\Route_Group;

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Route Collection
	 *
	 * @var RouteCollection
	 */
	protected $routes;

	/**
	 * All of the short-hand keys for middlewares.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * All of the middleware groups.
	 *
	 * @var array
	 */
	protected $middleware_groups = [];

	/**
	 * The registered route value binders.
	 *
	 * @var array
	 */
	protected $binders = [];

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app    = $app;
		$this->routes = new RouteCollection();
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function get( string $uri, $action = '' ) {
		return $this->add_route( [ 'GET', 'HEAD' ], $uri, $action );
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function post( string $uri, $action = '' ) {
		return $this->add_route( [ 'POST' ], $uri, $action );
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function put( string $uri, $action = '' ) {
		return $this->add_route( [ 'PUT' ], $uri, $action );
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function delete( string $uri, $action = '' ) {
		return $this->add_route( [ 'DELETE' ], $uri, $action );
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function patch( string $uri, $action = '' ) {
		return $this->add_route( [ 'PATCH' ], $uri, $action );
	}

	/**
	 * Register a OPTIONS route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 * @return Route
	 */
	public function options( string $uri, $action = '' ) {
		return $this->add_route( [ 'OPTIONS' ], $uri, $action );
	}

	/**
	 * Load the provided routes.
	 *
	 * @param  \Closure|string $routes
	 * @return void
	 */
	protected function load_routes( $routes ) {
		if ( $routes instanceof \Closure ) {
			$routes( $this );
		} else {
			( new Route_File_Registrar( $this ) )->register( $routes );
		}
	}

	/**
	 * Register a route.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 * @return Route
	 */
	public function add_route( array $methods, string $uri, $action ) {
		$route = $this->create_route( $methods, $uri, $action );

		$this->routes->add( $route->get_name(), $route );
		return $route;
	}

	/**
	 * Create a new route instance.
	 *
	 * @param array  $methods Methods to register.
	 * @param string $uri URL route.
	 * @param mixed  $action Route callback.
	 * @return Route
	 */
	protected function create_route( array $methods, string $uri, $action ): Route {
		$route = new Route( $methods, $this->prefix( $uri ), $action );

		if ( $this->has_group_stack() ) {
			$this->merge_group_attributes_into_route( $route );
		}

		return $route;
	}

	/**
	 * Prefix the given URI with the last prefix.
	 *
	 * @param string $uri Uri to prefix.
	 * @return string
	 */
	protected function prefix( string $uri ) {
		return trim( trim( $this->get_last_group_prefix(), '/' ) . '/' . trim( $uri, '/' ), '/' ) ?: '/';
	}

	/**
	 * Get registered routes.
	 *
	 * @return RouteCollection
	 */
	public function get_routes(): RouteCollection {
		return $this->routes;
	}

	/**
	 * Dispatch a request to the registered routes.
	 *
	 * @param Request $request Request object.
	 * @return Symfony_Response|null
	 */
	public function dispatch( Request $request ): ?Symfony_Response {
		return $this->execute_route_match(
			$this->match_route( $request ),
			$request
		);
	}

	/**
	 * Match a request to a registered route.
	 *
	 * @param Request $request Request object.
	 * @return array|null
	 */
	protected function match_route( Request $request ) {
		$context = new RequestContext();
		$context = $context->fromRequest( $request );
		$matcher = new UrlMatcher( $this->get_routes(), $context );

		return $matcher->matchRequest( $request );
	}

	/**
	 * Execute a route match and retrieve the response.
	 *
	 * @param array   $match Route match.
	 * @param Request $request Request object.
	 * @return Symfony_Response|null
	 *
	 * @throws HttpException Thrown on unknown route callback.
	 */
	protected function execute_route_match( $match, Request $request ): ?Symfony_Response {
		// Store the request parameters.
		$request->set_route_parameters( $match );
		$this->app->instance( 'request', $request );

		$route = Route::get_route_from_match( $match );

		if ( ! $route ) {
			throw new HttpException( 'Unknown route method: ' . \wp_json_encode( $match ) );
		}

		// Store the route match in the request object.
		$this->app['request']->set_route( $route );

		$middleware = $this->gather_route_middleware( $route );

		$response = ( new Pipeline( $this->app ) )
			->send( $this->app['request'] )
			->through( $middleware )
			->then(
				function( Request $request ) use ( $route ) {
					// Refresh the request object in the container with modifications from the middleware.
					$this->app['request'] = $request;

					return $route->run( $this->app );
				}
			);

			// do_action

		// Ensure the response is valid since the middleware can modify it after it is run through Route.
		return Route::ensure_response( $response );
	}

	/**
	 * Get all of the defined middleware short-hand names.
	 *
	 * @return array
	 */
	public function get_middleware() {
		return $this->middleware;
	}

	/**
	 * Register a short-hand name for a middleware.
	 *
	 * @param  string $name
	 * @param  string $class
	 * @return static
	 */
	public function alias_middleware( $name, $class ) {
		$this->middleware[ $name ] = $class;

		return $this;
	}

	/**
	 * Get all of the defined middleware groups.
	 *
	 * @return array
	 */
	public function get_middleware_groups() {
		return $this->middleware_groups;
	}

	/**
	 * Register a group of middleware.
	 *
	 * @param  string $name
	 * @param  array  $middleware
	 * @return static
	 */
	public function middleware_group( $name, array $middleware ) {
		$this->middleware_groups[ $name ] = $middleware;

		return $this;
	}

	/**
	 * Add a middleware to the beginning of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string $group
	 * @param  string $middleware
	 * @return static
	 */
	public function prepend_middleware_to_group( $group, $middleware ) {
		if ( isset( $this->middleware_groups[ $group ] ) && ! in_array( $middleware, $this->middleware_groups[ $group ] ) ) {
			array_unshift( $this->middleware_groups[ $group ], $middleware );
		}

		return $this;
	}

	/**
	 * Add a middleware to the end of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string $group
	 * @param  string $middleware
	 * @return static
	 */
	public function push_middleware_to_group( $group, $middleware ) {
		if ( ! array_key_exists( $group, $this->middleware_groups ) ) {
				$this->middleware_groups[ $group ] = [];
		}

		if ( ! in_array( $middleware, $this->middleware_groups[ $group ] ) ) {
				$this->middleware_groups[ $group ][] = $middleware;
		}

			return $this;
	}

	/**
	 * Gather the middleware for the given route with resolved class names.
	 *
	 * @todo Add excluded middleware support.
	 *
	 * @param Route $route Route instance.
	 * @return array
	 */
	public function gather_route_middleware( Route $route ): array {
		return collect( $route->middleware() )
			->map(
				function ( $name ) {
					return (array) Middleware_Name_Resolver::resolve( $name, $this->middleware, $this->middleware_groups );
				}
			)
			->flatten()
			->values()
			->to_array();
	}

	/**
	 * Add a new route parameter binder.
	 *
	 * @param string          $key
	 * @param string|callable $binder
	 */
	public function bind( string $key, $binder ) {
		$this->binders[ str_replace( '-', '_', $key ) ] = Route_Binding::for_callback(
			$this->app,
			$binder
		);
	}

	/**
	 * Register a model binder for a wildcard.
	 *
	 * @param string        $key
	 * @param string        $class
	 * @param \Closure|null $callback
	 */
	public function model( $key, $class, Closure $callback = null ) {
		$this->bind( $key, Route_Binding::for_model( $this->app, $class, $callback ) );
	}

	/**
	 * Substitute Explicit Bindings
	 *
	 * @param Request $request Request object.
	 */
	public function substitute_bindings( Request $request ) {
		foreach ( $request->get_route_parameters() as $key => $value ) {
			if ( ! isset( $this->binders[ $key ] ) ) {
				continue;
			}

			$request->set_route_parameter( $key, $this->perform_binding( $key, $value, $request ) );
		}
	}

	/**
	 * Call the binding callback for the given key.
	 *
	 * @param  string  $key Route key.
	 * @param  string  $value Value.
	 * @param  Request $request Request object.
	 * @return mixed
	 */
	protected function perform_binding( string $key, $value, Request $request ) {
		return call_user_func( $this->binders[ $key ], $value, $request );
	}

	/**
	 * Substitute the implicit Eloquent model bindings for the route.
	 *
	 * @param Request $request Request instance.
	 */
	public function substitute_implicit_bindings( Request $request ) {
		Implicit_Route_Binding::resolve_for_route( $this->app, $request );
	}

	/**
	 * Dynamically handle calls into the router instance.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Parameters for the method.
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		if ( 'middleware' === $method ) {
			return ( new Route_Registrar( $this ) )
				->attribute( $method, is_array( $parameters[0] ) ? $parameters[0] : $parameters );
		}

		return ( new Route_Registrar( $this ) )->attribute( $method, $parameters[0] );
	}
}
