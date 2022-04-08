<?php
/**
 * Route class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use ArrayAccess;
use ArrayObject;
use JsonSerializable;
use Mantle\Container\Container;
use Mantle\Contracts\Http\Routing\Router;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Database\Model\Model;
use Mantle\Http\Controller;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use ReflectionFunction;
use Mantle\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Route as Symfony_Route;
use Symfony\Component\HttpFoundation\Response as Symfony_Response;

use function Mantle\Support\Helpers\get_callable_fqn;

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
	 * @var \Mantle\Container\Container
	 */
	protected $container;

	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Name for the route.
	 *
	 * @var string
	 */
	protected $name;

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

		$this->setOption( 'utf8', true );
		$this->setMethods( $methods );

		// Store a reference to the route object inside of the Symfony route.
		$this->setDefault( static::ROUTE_OBJECT_KEY, $this );

		if ( is_callable( $action ) || is_string( $action ) ) {
			$action = [
				'callback' => $action,
			];
		} elseif (
			is_array( $action )
			&& ! empty( $action[0] )
			&& ! empty( $action[1] )
			&& is_string( $action[0] )
			&& is_string( $action[1] )
			&& class_exists( $action[0] )
		) {
			/**
			 * Handle controller 'static' style callbacks.
			 *
			 * They're written as callable-style (class name -> method). For PHP 8,
			 * they need to be manually detected since is_callable() will return false
			 * for non-static methods using the array structure.
			 */
			$action = [
				'callback' => $action,
			];
		}

		$this->action = $action;

		// Set the route name if it was passed in the action.
		if ( ! empty( $this->action['as'] ) ) {
			$this->name( (string) $this->action['as'] );
		} elseif ( ! empty( $this->action['name'] ) ) {
			$this->name( (string) $this->action['name'] );
		}
	}

	/**
	 * Set the route container.
	 *
	 * @param Router $router Router interface.
	 * @return static
	 */
	public function set_router( Router $router ) {
		$this->router = $router;
		return $this;
	}

	/**
	 * Get the route's name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		if ( isset( $this->name ) ) {
			return (string) $this->name;
		}

		// Fallback to the default route name.
		return strtolower( implode( '.', $this->getMethods() ) . ".{$this->getPath()}" );
	}

	/**
	 * Set the name for a route.
	 *
	 * @param string $name Name for the route.
	 * @return static
	 */
	public function name( string $name ) {
		/**
		 * Attempt to rename the route in the router.
		 *
		 * The route object is stored in a route collection as a reference but the
		 * route name is a static key for the collection.
		 */
		if ( isset( $this->router ) ) {
			$this->router->rename_route( $this->get_name(), $name );
		}

		$this->name = $name;

		return $this;
	}

	/**
	 * Get the action array or one of its properties for the route.
	 *
	 * @param string|null $key Key to get.
	 * @return mixed
	 */
	public function get_action( string $key = null ) {
		return Arr::get( $this->action, $key );
	}

	/**
	 * Set the action array for the route.
	 *
	 * @param array $action Action for the route.
	 * @return static
	 */
	public function set_action( array $action ) {
		$this->action = $action;
		return $this;
	}

	/**
	 * Get or set the middlewares attached to the route.
	 *
	 * @param  array|string|null $middleware Middleware to set, optional.
	 * @return static|array
	 */
	public function middleware( $middleware = null ) {
		if ( is_null( $middleware ) ) {
			return (array) ( $this->action['middleware'] ?? [] );
		}

		if ( is_string( $middleware ) ) {
			$middleware = func_get_args();
		}

		$this->action['middleware'] = array_merge(
			(array) ( $this->action['middleware'] ?? [] ),
			$middleware
		);

		return $this;
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
	public function run( Container $container ): ?Symfony_Response {
		$this->container = $container;

		if ( $this->has_controller_callback() ) {
			$response = $this->run_controller_callback();
		} elseif ( $this->has_callback() ) {
			$response = $this->run_callback();
		}

		if ( ! isset( $response ) ) {
			return null;
		}

		return $response ? static::ensure_response( $response ) : null;
	}


	/**
	 * Retrieve the route's callback name.
	 *
	 * @return string
	 */
	public function get_callback_name(): string {
		if ( $this->has_controller_callback() ) {
			$controller = $this->get_controller_name();
			$method     = $this->get_controller_method();

			return get_callable_fqn( [ $controller, $method ] );
		} elseif ( $this->has_callback() ) {
			return get_callable_fqn( $this->action['callback'] );
		}

		return '';
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
		if ( empty( $this->action['callback'] ) ) {
			return false;
		}

		if ( is_string( $this->action['callback'] ) ) {
			// Check for Controller@method callback.
			if ( Str::contains( $this->action['callback'], '@' ) ) {
				return true;
			}

			// Assume it is invokable.
			$this->action['callback'] = static::make_invokable( $this->action['callback'] );
			return true;
		}

		if ( is_array( $this->action['callback'] ) ) {
			[ $controller ] = $this->action['callback'];

			if ( class_exists( $controller ) && is_subclass_of( $controller, Controller::class ) ) {
				return true;
			}
		}

		return false;
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
		if ( is_string( $this->action['callback'] ) ) {
			return Str::parse_callback( $this->action['callback'] );
		}

		return $this->action['callback'];
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
	 * @return Symfony_Response
	 */
	public static function ensure_response( $response ): Symfony_Response {
		if ( $response instanceof Response || $response instanceof Symfony_Response ) {
			return $response;
		}

		if (
			is_array( $response )
			|| $response instanceof Arrayable
			|| $response instanceof ArrayAccess
			|| $response instanceof JsonSerializable
			|| $response instanceof ArrayObject
			|| $response instanceof Model
		) {
			return new JsonResponse( $response );
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

	/**
	 * Get the parameters that are listed in the route / controller signature.
	 *
	 * @param string|null $sub_class Subclass to verify the parameter is an instance of.
	 * @return array
	 */
	public function get_signature_parameters( string $sub_class = null ) {
		return Route_Signature_Parameters::from_action( $this->action, $sub_class );
	}

	/**
	 * Make an action for an invokable controller.
	 *
	 * @param string $action
	 * @return string
	 *
	 * @throws \UnexpectedValueException Thrown on missing method.
	 */
	protected static function make_invokable( string $action ): string {
		if ( ! method_exists( $action, '__invoke' ) ) {
			throw new \UnexpectedValueException( "Invalid route action: [{$action}]." );
		}

		return "{$action}@__invoke";
	}
}
