<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http;

use Exception;
use InvalidArgumentException;
use Mantle\Framework\Application;
use Mantle\Framework\Contracts\Http\Kernel as Kernel_Contract;
use Mantle\Framework\Contracts\Kernel as Core_Kernel_Contract;
use Mantle\Framework\Contracts\Providers\Route_Service_Provider as Route_Service_Provider_Contract;
use Mantle\Framework\Facade\Facade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Mantle\Framework\Http\Request;
use Mantle\Framework\Http\Routing\Route;
use Mantle\Framework\Support\Str;

/**
 * HTTP Kernel
 */
class Kernel implements Kernel_Contract, Core_Kernel_Contract {
	/**
	 * The application implementation.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Request instance.
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array
	 */
	protected $bootstrappers = [
		\Mantle\Framework\Bootstrap\Load_Configuration::class,
		\Mantle\Framework\Bootstrap\Register_Facades::class,
		\Mantle\Framework\Bootstrap\Register_Providers::class,
		\Mantle\Framework\Bootstrap\Boot_Providers::class,
	];

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Bootstrap the kernel and send the request through the router.
	 *
	 * @todo Add better error handling.
	 *
	 * @param Request $request Request instance.
	 */
	public function handle( Request $request ) {
		try {
			$this->bootstrap();
		} catch ( Exception $e ) {
			\wp_die( 'Error booting HTTP Kernel: ' . $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$this->request = $request;

		// Setup the Request Facade.
		$this->app->instance( 'request', $request );
		Facade::clear_resolved_instance( 'request' );

		\add_action( 'wp_loaded', [ $this, 'on_wp_loaded' ] );
	}

	/**
	 * Send the request through the HTTP Router
	 */
	public function on_wp_loaded() {
		$response = $this->send_request( $this->request );
		if ( ! $response ) {
			return;
		}

		$response->send();
		exit;
	}

	/**
	 * Bootstrap the console.
	 */
	public function bootstrap() {
		$this->app->bootstrap_with( $this->bootstrappers(), $this );
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers(): array {
		return $this->bootstrappers;
	}

	/**
	 * Send the request through the router.
	 *
	 * @param Request $request Request object.
	 * @return Response|null
	 *
	 * @throws InvalidArgumentException Thrown on invalid router service provider instance.
	 * @throws ResourceNotFoundException Thrown on missing resource.
	 */
	protected function send_request( Request $request ): ?Response {
		$provider = $this->app['router.service-provider'];

		if ( ! ( $provider instanceof Route_Service_Provider_Contract ) ) {
			throw new InvalidArgumentException( 'Unknown "router.service-provider" instance: ' . get_class( $provider ) );
		}

		try {
			$match = $this->match_route( $request );
		} catch ( ResourceNotFoundException $e ) {
			// If no route found, allow the request to be passed down to WordPress.
			if ( $provider->should_pass_through_requests( $request ) ) {
				return null;
			}

			throw $e;
		}

		return $this->execute_route_match( $match );
	}

	/**
	 * Match the current request with registered routes.
	 *
	 * @param Request $request Request instance.
	 * @return array
	 */
	protected function match_route( Request $request ): array {
		$router  = $this->app['router'];
		$context = new RequestContext();
		$context = $context->fromRequest( $request );
		$matcher = new UrlMatcher( $router->get_routes(), $context );

		return $matcher->matchRequest( $request );
	}

	/**
	 * Get a response callback from a route match.
	 *
	 * @param array $match Route match.
	 * @return callable
	 *
	 * @throws Http_Exception Thrown on unknown route callback.
	 */
	protected function execute_route_match( array $match ) {

		if ( ! empty( $match['route'] ) && $match['route'] instanceof Route ) {
			return $match['route']->render( $this->app );
		}

		throw new Http_Exception( 'Unknown route method: ' . \wp_json_encode( $match ) );
	}

	protected function get_response_from_controller( array $match ) {

	}

	/**
	 * Get the controller name used for the route.
	 *
	 * @return string
	 */
	protected function get_controller_name(): string {
		return $this->parseControllerCallback()[0] ?? '';
	}

	/**
	 * Get the controller method used for the route.
	 *
	 * @return string
	 */
	protected function get_controller_method(): string {
		return $this->parseControllerCallback()[1] ?? '';
	}

	/**
	 * Parse the controller.
	 *
	 * @return array
	 */
	protected function parse_controller_callback() {
		return Str::parse_callback($this->action['uses']);
	}
}
