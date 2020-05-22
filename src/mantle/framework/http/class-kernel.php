<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http;

use Exception;
use Mantle\Framework\Application;
use Mantle\Framework\Contracts\Http\Kernel as Kernel_Contract;
use Mantle\Framework\Contracts\Kernel as Core_Kernel_Contract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

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

		$response = $this->send_request( $request );
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
	 */
	protected function send_request( Request $request ): ?Response {
		try {
			$router  = $this->app['router'];
			$context = new RequestContext();
			$context = $context->fromRequest( $request );
			$matcher = new UrlMatcher( $router->get_routes(), $context );

			$match = $matcher->matchRequest( $request );
		} catch ( ResourceNotFoundException $e ) {
			unset( $e );

			// If no route found, allow the request to be passed down to WordPress.
			// todo: allow this to be prevented and Mantle control the entire thing!
			return null;
		}

		$response = $this->app->call( $this->get_response( $match ) );

		return $this->ensure_response( $response );
	}

	/**
	 * Get a response callback from a route match.
	 *
	 * @param array $match Route match.
	 * @return callable
	 *
	 * @throws Http_Exception Thrown on unknown route callback.
	 */
	protected function get_response( array $match ) {
		if ( isset( $match['action'] ) ) {
			if ( is_callable( $match['action'] ) ) {
				return $match['action'];
			}

			// todo: translate a controller method.
		}

		throw new Http_Exception( 'Unknown route method: ' . \wp_json_encode( $match ) );
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
}
