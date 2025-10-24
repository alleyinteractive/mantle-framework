<?php
/**
 * Router class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Closure;
use InvalidArgumentException;
use Mantle\Contracts\Container;
use Mantle\Contracts\Events\Dispatcher;
use Mantle\Contracts\Http\Routing\Route_Registrar as Registrar_Contract;
use Mantle\Contracts\Http\Routing\Router as Router_Contract;
use Mantle\Http\Request;
use Mantle\Http\Routing\Events\Route_Matched;
use Mantle\Support\Arr;
use Mantle\Support\Pipeline;
use Mantle\Support\Traits\Macroable;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response as Symfony_Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

use function Mantle\Support\Helpers\collect;

/**
 * Mantle Router
 *
 * Allow registration of routes to the application. On 'parse_request', the
 * request will be dispatched to the router by the HTTP kernel.
 *
 * @mixin \Mantle\Http\Routing\Route_Registrar
 */
class Router implements Router_Contract {
	use Concerns\Route_Group;
	use Macroable {
		__call as macro_call;
	}

	/**
	 * Route Collection
	 */
	protected RouteCollection $routes;

	/**
	 * REST API route collection.
	 */
	protected RouteCollection $rest_routes;

	/**
	 * All of the short-hand keys for middlewares.
	 *
	 * @var array<string, class-string>
	 */
	protected array $middleware = [];

	/**
	 * All of the middleware groups.
	 *
	 * @var array<string, array<string>>
	 */
	protected array $middleware_groups = [];

	/**
	 * The registered route value binders.
	 *
	 * @var array<string, \Closure>
	 */
	protected array $binders = [];

	/**
	 * Current route registrar.
	 */
	public ?Registrar_Contract $registrar = null;

	/**
	 * Flag or callback to determine if requests should pass through to WordPress.
	 *
	 * @var bool|callable
	 */
	protected mixed $pass_requests_to_wordpress = true;

	/**
	 * Constructor.
	 *
	 * @param Dispatcher $events Events dispatcher.
	 * @param Container  $container Container instance.
	 */
	public function __construct( protected Dispatcher $events, protected Container $container ) {
		$this->routes      = new RouteCollection();
		$this->rest_routes = new RouteCollection();
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function get( string $uri, mixed $action = '' ): Route {
		return $this->with_registrar(
			fn () => $this->registrar->register_route( 'get', $uri, $action ),
		);
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function post( string $uri, mixed $action = '' ): Route {
		return $this->with_registrar(
			fn () => $this->registrar->register_route( 'post', $uri, $action ),
		);
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function put( string $uri, mixed $action = '' ): Route {
		return $this->with_registrar(
			fn () => $this->registrar->register_route( 'put', $uri, $action ),
		);
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function delete( string $uri, mixed $action = '' ): Route {
		return $this->with_registrar(
			fn () => $this->registrar->register_route( 'delete', $uri, $action ),
		);
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function patch( string $uri, mixed $action = '' ): Route {
		return $this->with_registrar(
			fn () => $this->registrar->register_route( 'patch', $uri, $action ),
		);
	}

	/**
	 * Register a OPTIONS route.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function options( string $uri, mixed $action = '' ): Route {
		return $this->with_registrar(
			fn () => $this->registrar->register_route( 'options', $uri, $action ),
		);
	}

	/**
	 * Register a route for any HTTP method.
	 *
	 * @param string $uri URL to register for.
	 * @param mixed  $action Callback action.
	 */
	public function any( string $uri, mixed $action = '' ): Route {
		return $this->with_registrar(
			fn () => $this->registrar->register_route( 'any', $uri, $action ),
		);
	}

	/**
	 * Load the provided routes.
	 *
	 * @param  \Closure|string $routes
	 * @return void
	 */
	protected function load_routes( \Closure|string $routes ) {
		if ( $routes instanceof \Closure ) {
			$routes( $this );
		} else {
			( new Route_File_Registrar( $this ) )->register( $routes );
		}
	}

	/**
	 * Register a route.
	 *
	 * @param string[]     $methods Methods to register.
	 * @param string       $uri URL route.
	 * @param array<mixed> $arguments Route callback.
	 */
	public function add_route( array $methods, string $uri, array $arguments ): Route {
		$route = $this->create_route( $methods, $uri, $arguments );

		$this->routes->add( $route->get_name(), $route );

		return $route;
	}

	/**
	 * Register a REST API route.
	 *
	 * @param string[]     $methods Methods to register.
	 * @param string       $uri URL route.
	 * @param array<mixed> $arguments Route arguments.
	 */
	public function add_rest_route( array $methods, string $uri, array $arguments ): Route {
		$route = $this->create_route( $methods, $uri, $arguments );

		$this->rest_routes->add( $route->get_name(), $route );

		return $route;
	}

	/**
	 * Create a new route instance.
	 *
	 * @param string[]     $methods Methods to register.
	 * @param string       $uri URL route.
	 * @param array<mixed> $action Route action/arguments.
	 */
	protected function create_route( array $methods, string $uri, array $action ): Route {
		$route = new Route( $methods, $this->prefix( $uri ), $action );

		if ( $this->has_group_stack() ) {
			$this->merge_group_attributes_into_route( $route );
		}

		$route->set_router( $this );

		return $route;
	}

	/**
	 * Invoke a callback with the route registrar set.
	 *
	 * If it is not set, the default route registrar will be created and set to
	 * null after the callback is invoked.
	 *
	 * @template TData = mixed
	 *
	 * @param \Closure $callback Callback to invoke with the registrar.
	 * @param bool     $clear Always clear the registrar after the callback is invoked. By default, the registrar will be cleared only if it was not set before.
	 * @phpstan-param (\Closure(\Mantle\Contracts\Http\Routing\Route_Registrar $registrar): TData) $callback
	 * @phpstan-return TData
	 */
	protected function with_registrar( \Closure $callback, bool $clear = false ): mixed {
		$set = ! is_null( $this->registrar );

		if ( ! $set ) {
			$this->registrar = new Route_Registrar( $this );
		}

		$value = $callback( $this->registrar );

		if ( ! $set || $clear ) {
			$this->registrar = null;
		}

		return $value;
	}

	/**
	 * Prefix the given URI with the last prefix.
	 *
	 * @param string $uri Uri to prefix.
	 */
	protected function prefix( string $uri ): string {
		return trim( trim( $this->get_last_group_prefix(), '/' ) . '/' . trim( $uri, '/' ), '/' ) ?: '/';
	}

	/**
	 * Get registered routes.
	 */
	public function get_routes(): RouteCollection {
		return $this->routes;
	}

	/**
	 * Retrieve the container/application instance.
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Dispatch a request to the registered routes.
	 *
	 * @param Request $request Request object.
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
	 * @return array<mixed>|null Route match.
	 */
	protected function match_route( Request $request ): ?array {
		$context = ( new RequestContext() )->fromRequest( $request );

		return ( new UrlMatcher( $this->get_routes(), $context ) )->matchRequest( $request );
	}

	/**
	 * Execute a route match and retrieve the response.
	 *
	 * @param array<mixed> $match Route match.
	 * @param Request      $request Request object.
	 *
	 * @throws HttpException Thrown on unknown route callback.
	 */
	protected function execute_route_match( $match, Request $request ): ?Symfony_Response {
		// Store the request parameters.
		$request->set_route_parameters( $match );
		$this->container->instance( 'request', $request );

		$route = Route::get_route_from_match( $match );

		if ( ! $route instanceof \Mantle\Http\Routing\Route ) {
			throw new HttpException( 500, 'Unknown route method: ' . \wp_json_encode( $match ) );
		}

		// Store the route match in the request object.
		$this->container['request']->set_route( $route );

		$this->events->dispatch( new Route_Matched( $route, $request ) );

		$middleware = $this->gather_route_middleware( $route );

		$response = ( new Pipeline( $this->container ) )
			->send( $this->container['request'] )
			->through( $middleware )
			->then(
				function ( Request $request ) use ( $route ): ?\Symfony\Component\HttpFoundation\Response {
					// Refresh the request object in the container with modifications from the middleware.
					$this->container['request'] = $request;

					return $route->run( $this->container );
				}
			);

		// Ensure the response is valid since the middleware can modify it after it is run through Route.
		return static::to_response( $request, $response );
	}

	/**
	 * Prepare a response for sending.
	 *
	 * @param Request $request
	 * @param mixed   $response
	 */
	public static function to_response( Request $request, mixed $response ): \Symfony\Component\HttpFoundation\Response {
		return Route::ensure_response( $response )->prepare( $request );
	}

	/**
	 * Get all of the defined middleware short-hand names.
	 *
	 * @return array<string, class-string>
	 */
	public function get_middleware(): array {
		return $this->middleware;
	}

	/**
	 * Register a short-hand name for a middleware.
	 *
	 * @param  string $name
	 * @param  string $class
	 */
	public function alias_middleware( string $name, string $class ): static {
		$this->middleware[ $name ] = $class;

		return $this;
	}

	/**
	 * Get all of the defined middleware groups.
	 *
	 * @return array<string, array<class-string>>
	 */
	public function get_middleware_groups(): array {
		return $this->middleware_groups;
	}

	/**
	 * Register a group of middleware.
	 *
	 * @param  string              $name
	 * @param  array<class-string> $middleware
	 */
	public function middleware_group( string $name, array $middleware ): static {
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
	 */
	public function prepend_middleware_to_group( string $group, string $middleware ): static {
		if ( isset( $this->middleware_groups[ $group ] ) && ! in_array( $middleware, $this->middleware_groups[ $group ], true ) ) {
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
	 */
	public function push_middleware_to_group( string $group, string $middleware ): static {
		if ( ! array_key_exists( $group, $this->middleware_groups ) ) {
				$this->middleware_groups[ $group ] = [];
		}

		if ( ! in_array( $middleware, $this->middleware_groups[ $group ], true ) ) {
				$this->middleware_groups[ $group ][] = $middleware;
		}

		return $this;
	}

	/**
	 * Gather the middleware for the given route with resolved class names.
	 *
	 * @param Route $route Route instance.
	 * @return array<string|class-string>
	 */
	public function gather_route_middleware( Route $route ): array {
		$middleware = $route->excluded_middleware();

		// If the route has a wildcard, we will just skip the middleware gathering.
		if ( in_array( '*', $middleware, true ) ) {
			return [];
		}

		$excluded = collect( $route->excluded_middleware() )
			->map(
				fn ( \Closure|string $name ) => Middleware_Name_Resolver::resolve( $name, $this->middleware, $this->middleware_groups ),
			)
			->flatten()
			->values()
			->all();

		return collect( $route->middleware() )
			->map(
				fn ( \Closure|string $name ) => (array) Middleware_Name_Resolver::resolve( $name, $this->middleware, $this->middleware_groups ),
			)
			->flatten()
			->reject(
				function ( $name ) use ( $excluded ) {
					if ( empty( $excluded ) ) {
						return false;
					}

					if ( $name instanceof Closure ) {
						return false;
					}

					if ( in_array( $name, $excluded, true ) ) {
						return true;
					}

					$reflection = new ReflectionClass( $name );

					return collect( $excluded )->contains(
						fn ( $exclude ) => class_exists( $exclude ) && $reflection->isSubclassOf( $exclude )
					);
				}
			)
			->values()
			->all();
	}

	/**
	 * Add a new route parameter binder.
	 *
	 * @param string          $key
	 * @param string|callable $binder
	 */
	public function bind( string $key, $binder ): void {
		$this->binders[ str_replace( '-', '_', $key ) ] = Route_Binding::for_callback(
			$this->container,
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
	public function bind_model( string $key, string $class, ?Closure $callback = null ): void {
		$this->bind( $key, Route_Binding::for_model( $this->container, $class, $callback ) );
	}

	/**
	 * Substitute Explicit Bindings
	 *
	 * @param Request $request Request object.
	 */
	public function substitute_bindings( Request $request ): void {
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
	 */
	protected function perform_binding( string $key, $value, Request $request ): mixed {
		return call_user_func( $this->binders[ $key ], $value, $request );
	}

	/**
	 * Substitute the implicit Eloquent model bindings for the route.
	 *
	 * @param Request $request Request instance.
	 */
	public function substitute_implicit_bindings( Request $request ): void {
		Implicit_Route_Binding::resolve_for_route( $this->container, $request );
	}

	/**
	 * Register a REST API route.
	 *
	 * @param string                       $namespace        Namespace for the REST API route.
	 * @param callable|string              $callback_or_uri  Callback that will be invoked to register
	 *                                                       routes or a string route path.
	 * @param callable|array<mixed>|string $args             Callback for the route if $callback or route arguments.
	 */
	public function rest_api( string $namespace, callable|string $callback_or_uri, callable|array|string $args = [] ): ?Route {
		$namespace = trim( $namespace, '/' );

		$this->registrar = new Rest_Route_Registrar( router: $this, namespace: $namespace );

		if ( is_callable( $callback_or_uri ) ) {
			$this->with_registrar( $callback_or_uri, clear: true );

			return null;
		}

		// If a third argument is a callable we will assume it is the action and the
		// second argument is the route.
		if ( is_callable( $args ) ) {
			$route = $this->registrar->register_route(
				method: [ 'GET', 'HEAD' ],
				uri: $callback_or_uri,
				action: $args,
			);

			$this->registrar = null;

			return $route;
		}

		$args['methods'] = isset( $args['methods'] )
			? Arr::wrap( $args['methods'] )
			: [ 'GET', 'HEAD' ];

		return $this->with_registrar(
			fn () => $this->registrar->register_route(
				method: $args['methods'],
				uri: $callback_or_uri,
				action: $args,
			),
			clear: true,
		);
	}

	/**
	 * Register routing for a WordPress model.
	 *
	 * @param string $model Model class name.
	 * @param string $controller Controller class name.
	 */
	public function model( string $model, string $controller ): void {
		$this->container->make( Entity_Router::class )->add( $this, $model, $controller );
	}

	/**
	 * Dynamically handle calls into the router instance that should be forwarded
	 * to the current route registrar.
	 *
	 * @param string       $method Method name.
	 * @param array<mixed> $parameters Parameters for the method.
	 */
	public function __call( string $method, array $parameters ): mixed {
		if ( static::has_macro( $method ) ) {
			return $this->macro_call( $method, $parameters );
		}

		$registrar = $this->registrar ?: new Route_Registrar( $this );

		return $registrar->{$method}( ...$parameters );
	}

	/**
	 * Sync the routes to the URL generator.
	 */
	public function sync_routes_to_url_generator(): void {
		$this->container['url']->set_routes( $this->routes );
	}

	/**
	 * Rename a route.
	 *
	 * @param string $old_name Old route name.
	 * @param string $new_name New route name.
	 *
	 * @throws \InvalidArgumentException Thrown when attempting to rename a route
	 *                                  a name that is already taken.
	 */
	public function rename_route( string $old_name, string $new_name ): static {
		$old = $this->routes->get( $old_name );

		if ( ! $old instanceof \Symfony\Component\Routing\Route ) {
			return $this;
		}

		$new = $this->routes->get( $new_name );

		if ( $new instanceof \Symfony\Component\Routing\Route ) {
			throw new InvalidArgumentException( "Unable to rename route, name already taken. [{$old_name} => {$new_name}]" );
		}

		$this->routes->add( $new_name, $old );
		$this->routes->remove( $old_name );

		return $this;
	}

	/**
	 * Determine if the request should pass through to WordPress.
	 *
	 * @param (callable(\Mantle\Http\Request): bool)|bool $callback Callback to determine if the request should pass through to WordPress.
	 */
	public function pass_requests_to_wordpress( callable|bool $callback ): static {
		$this->pass_requests_to_wordpress = $callback;

		return $this;
	}

	/**
	 * Determine if the request should pass through to WordPress.
	 *
	 * @param Request $request Request object.
	 */
	public function should_pass_through_request( Request $request ): bool {
		// Early checks to always allow the REST API and prevent routing when not
		// using themes.
		if ( str_starts_with( $request->path(), rest_get_url_prefix() ) ) {
			return true;
		}

		if ( ! wp_using_themes() ) {
			return true;
		}

		$status = $this->pass_requests_to_wordpress;

		return is_callable( $status ) ? (bool) $status( $request ) : $status;
	}

	/**
	 * Register the REST API routes from the router with WordPress.
	 */
	public function register_rest_routes(): void {
		if ( $this->rest_routes->count() === 0 ) {
			return;
		}

		foreach ( $this->rest_routes as $rest_route ) {
			assert( $rest_route instanceof Route );

			$rest_route->register_rest_route();
		}
	}
}
