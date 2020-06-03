<?php
/**
 * Route class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use Mantle\Framework\Container\Container;
use Mantle\Framework\Support\Str;
use ReflectionFunction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route as Symfony_Route;

/**
 * Route Class
 */
class Route extends Symfony_Route {
	use Route_Dependency_Resolver;

	/**
	 * Key to store the Mantle Route object inside of the Symfony Route.
	 *
	 * @var string
	 */
	public const ROUTE_OBJECT_KEY = '_mantle_route';

	/**
	 * Route action.
	 *
	 * @var array
	 */
	protected $action;

	/**
	 * Container instance.
	 *
	 * @var \Mantle\Framework\Container\Container
	 */
	protected $container;

	/**
	 * Get the route object from a Symfony route match.
	 *
	 * @param array $match Route match.
	 * @return Route|null
	 */
	public static function get_route_from_match( array $match ): ?Route {
		if ( ! empty( $match[ static::ROUTE_OBJECT_KEY ] ) && $match[ static::ROUTE_OBJECT_KEY ] instanceof Route ) {
			return $match[ static::ROUTE_OBJECT_KEY ];
		}

		return null;
	}

	/**
	 * Constructor.
	 *
	 * @param array          $methods HTTP methods the route responds to.
	 * @param string         $path The path the route responds to.
	 * @param \Closure|array $action The route callback or array of actions.
	 */
	public function __construct( array $methods, string $path, $action ) {
		parent::__construct( $path );

		$this->setMethods( $methods );

		// Store a reference to the route object inside of the Symfony route.
		$this->setDefault( static::ROUTE_OBJECT_KEY, $this );

		if ( is_callable( $action ) || is_string( $action ) ) {
			$action = [
				'callback' => $action,
			];
		}

		$this->action = $action;
	}

	/**
	 * Get the route's name.
	 *
	 * @return string
	 */
	public function get_route_name(): string {
		if ( is_array( $this->action ) && ! empty( $this->action['name'] ) ) {
			return $this->action['name'];
		}

		$uri = $this->getPath();
		return implode( ':', $this->getMethods() ) . ":{$uri}";
	}

	/**
	 * Set a callback for a route.
	 *
	 * @param callable $callback Callback to invoke.
	 * @return static
	 */
	public function callback( $callback ) {
		$this->action['callback'] = $callback;
		return $this;
	}

	/**
	 * Render a route.
	 *
	 * @todo Add route parameters from the request (pass :slug down to the route).
	 *
	 * @param Container $container Service Container.
	 * @return Response|null
	 */
	public function run( Container $container ): ?Response {
		$this->container = $container;

		if ( $this->has_callback() ) {
			$response = $this->run_callback();
		} elseif ( $this->has_controller_callback() ) {
			$response = $this->run_controller_callback();
		}

		return $response ? $this->ensure_response( $response ) : null;
	}

	/**
	 * Determine if the route has a closure callback.
	 *
	 * @return bool
	 */
	protected function has_callback(): bool {
		return ! empty( $this->action['callback'] ) && is_callable( $this->action['callback'] );
	}

	/**
	 * Determine if the route has a controller callback.
	 *
	 * @return bool
	 */
	protected function has_controller_callback(): bool {
		return ! empty( $this->action['callback'] ) && Str::contains( $this->action['callback'], '@' );
	}

	/**
	 * Get the controller name used for the route.
	 *
	 * @return string
	 */
	protected function get_controller_name(): string {
		return $this->parse_controller_callback()[0] ?? '';
	}

	/**
	 * Get the controller method used for the route.
	 *
	 * @return string
	 */
	protected function get_controller_method(): string {
		return $this->parse_controller_callback()[1] ?? '';
	}

	/**
	 * Parse the controller.
	 *
	 * @return array
	 */
	protected function parse_controller_callback() {
		return Str::parse_callback( $this->action['callback'] );
	}

	/**
	 * Get the controller's closure callback.
	 *
	 * @return callable:null
	 */
	protected function get_callback(): ?callable {
		return $this->has_callback() ? $this->action['callback'] : null;
	}

	/**
	 * Run the route's closure callback.
	 *
	 * @return mixed
	 */
	protected function run_callback() {
		$callback   = $this->get_callback();
		$parameters = $this->resolve_method_dependencies(
			$this->get_request_parameters(),
			new ReflectionFunction( $callback )
		);

		return $callback(
			...array_values( $parameters )
		);
	}

	/**
	 * Run the controller callback.
	 *
	 * @return mixed
	 */
	protected function run_controller_callback() {
		$controller = $this->get_controller_name();
		$method     = $this->get_controller_method();

		$parameters = $this->resolve_class_method_dependencies(
			$this->get_request_parameters(),
			$controller,
			$method
		);

		$controller = $this->container->make( $controller );

		if ( method_exists( $controller, 'call_action' ) ) {
			return $controller->call_action( $method, $parameters );
		}

		return $controller->{ $method }( ...array_values( $parameters ) );
	}

	/**
	 * Ensure a proper response object.
	 *
	 * @param mixed $response Response to send.
	 * @return Response
	 */
	protected function ensure_response( $response ): Response {
		if ( $response instanceof Response ) {
			return $response;
		}

		return new Response( $response );
	}

	/**
	 * Get the route parameters.
	 *
	 * @return array
	 */
	public function get_request_parameters(): array {
		return $this->container['request']->get_route_parameters()->all();
	}
}
