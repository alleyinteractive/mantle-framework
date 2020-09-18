<?php
/**
 * Rest_Route_Registrar class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

/**
 * REST API Route Registrar
 */
class Rest_Route_Registrar {
	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Queued routes to register.
	 *
	 * @var array
	 */
	protected $routes;

	/**
	 * Namespace to register to.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Constructor.
	 *
	 * @param Router $router Router instance.
	 * @param string $namespace Namespace to register to.
	 */
	public function __construct( Router $router, string $namespace ) {
		$this->router    = $router;
		$this->namespace = $namespace;

		add_action( 'rest_api_init', [ $this, 'register_routes' ], 20 );
	}

	/**
	 * Register a REST API Route.
	 *
	 * @param string         $route Route to register.
	 * @param array|callable $args Arguments or callback for the route.
	 */
	public function register_route( string $route, $args = [] ) {
		$args = $this->normalize_args( $args );

		if ( $this->should_queue() ) {
			$this->routes[] = [ $route, $args ];
		} else {
			register_rest_route( $this->namespace, $route, $args );
		}
	}

	/**
	 * Normalize the arguments that are registered.
	 *
	 * @param array|callable $args Arguments for the route or callback function.
	 * @return array
	 */
	protected function normalize_args( $args ): array {
		if ( is_callable( $args ) ) {
			$args = [
				'callback' => $args,
			];
		}

		// Fill in the required argument for permission callback.
		if ( empty( $args['permission_callback'] ) ) {
			$args['permission_callback'] = '__return_true';
		}

		// Ensure the callback returns a valid REST response.
		if ( isset( $args['callback'] ) ) {
			$args['callback'] = $this->wrap_callback( $args['callback'] );
		}

		return $args;
	}

	/**
	 * Wrap the route callback with a valid WordPress REST response.
	 *
	 * @param callable $callback Callback to invoke.
	 * @return callable
	 */
	protected function wrap_callback( callable $callback ): callable {
		return function( ...$args ) use ( $callback ) {
			return rest_ensure_response( $callback( ...$args ) );
		};
	}

	/**
	 * Register the queued routes.
	 */
	public function register_routes() {
		foreach ( $this->routes as $route ) {
			register_rest_route( $this->namespace, ...$route );
		}
	}

	/**
	 * Determine if the routes should be registered now.
	 *
	 * @return bool
	 */
	protected function should_queue(): bool {
		return ! did_action( 'rest_api_init' );
	}
}
