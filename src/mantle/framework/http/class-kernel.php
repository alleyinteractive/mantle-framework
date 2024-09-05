<?php
/**
 * Kernel class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http;

use InvalidArgumentException;
use Mantle\Contracts\Application;
use Mantle\Contracts\Http\Kernel as Kernel_Contract;
use Mantle\Contracts\Http\Routing\Router;
use Mantle\Contracts\Kernel as Core_Kernel_Contract;
use Mantle\Contracts\Providers\Route_Service_Provider as Route_Service_Provider_Contract;
use Mantle\Facade\Facade;
use Mantle\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;
use Mantle\Contracts\Exceptions\Handler as Exception_Handler;

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
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Request instance.
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array<int, class-string>
	 */
	protected $bootstrappers = [
		\Mantle\Framework\Bootstrap\Load_Configuration::class,
		\Mantle\Framework\Bootstrap\Register_Aliases::class,
		\Mantle\Framework\Bootstrap\Register_Providers::class,
		\Mantle\Framework\Bootstrap\Boot_Providers::class,
	];

	/**
	 * The application's middleware stack.
	 *
	 * @var array<int, callable|class-string>
	 */
	protected $middleware = [];

	/**
	 * The application's route middleware groups.
	 *
	 * @var array<string, array<int, callable|class-string>>
	 */
	protected $middleware_groups = [
		'web'      => [
			\Mantle\Http\Routing\Middleware\Setup_WordPress::class,
			\Mantle\Http\Routing\Middleware\Substitute_Bindings::class,
			\Mantle\Http\Routing\Middleware\Wrap_Template::class,
		],
		'rest-api' => [],
	];

	/**
	 * The application's route middleware.
	 *
	 * @var array<string, callable|class-string>
	 */
	protected $route_middleware = [
		'can' => \Mantle\Auth\Middleware\Authorize::class,
	];

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 * @param Router      $router Router instance.
	 */
	public function __construct( Application $app, Router $router ) {
		$this->app    = $app;
		$this->router = $router;

		$this->sync_middleware_to_router();
	}

	/**
	 * Bootstrap the kernel and send the request through the router.
	 *
	 * @todo Add better error handling.
	 *
	 * @param Request $request Request instance.
	 */
	public function handle( Request $request ): void {
		$this->request = $request;

		// Setup the Request Facade.
		$this->app->instance( 'request', $request );

		Facade::clear_resolved_instance( 'request' );

		try {
			$this->bootstrap();
		} catch ( Throwable $e ) {
			$this->report_exception( $e );

			$response = $this->render_exception( $request, $e );

			if ( $response instanceof Response ) {
				$response->send();
				exit;
			} else {
				\wp_die( 'Error booting HTTP Kernel: ' . $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		if ( did_action( 'parse_request' ) ) {
			$this->handle_request();
		} else {
			\add_action( 'parse_request', fn () => $this->handle_request() );
		}
	}

	/**
	 * Terminate the kernel.
	 *
	 * @param Request $request Request instance.
	 * @param mixed   $response Response instance.
	 */
	public function terminate( Request $request, mixed $response ): void {
		// Send the request early to allow for the termination callback to be fired
		// after the response is sent.
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		}

		$this->app->terminate();
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * Send the request through the HTTP Router and optional send the response. Called on
	 * the 'wp_loaded' filter.
	 */
	protected function handle_request() {
		$response = $this->send_request_through_router( $this->request );

		if ( ! $response ) {
			// Register the termination callback to be called on shutdown.
			add_action( 'shutdown', fn () => $this->terminate( $this->request, null ), 100 );

			return;
		}

		$response->send();

		$this->terminate( $this->request, $response );

		exit;
	}

	/**
	 * Bootstrap the console.
	 */
	public function bootstrap(): void {
		if ( ! $this->app->has_been_bootstrapped() ) {
			$this->app->bootstrap_with( $this->bootstrappers(), $this );
		}
	}

	/**
	 * Get the bootstrap classes for the application.
	 */
	protected function bootstrappers(): array {
		return $this->bootstrappers;
	}

	/**
	 * Sync the current state of the middleware to the router.
	 *
	 * @return void
	 */
	protected function sync_middleware_to_router() {
		foreach ( $this->middleware_groups as $key => $middleware ) {
			$this->router->middleware_group( $key, $middleware );
		}

		foreach ( $this->route_middleware as $key => $middleware ) {
			$this->router->alias_middleware( $key, $middleware );
		}
	}

	/**
	 * Send the request through the router.
	 *
	 * @param Request $request Request object.
	 *
	 * @throws InvalidArgumentException Thrown on invalid router service provider instance.
	 */
	public function send_request_through_router( Request $request ): ?Response {
		if ( is_admin() || ! wp_using_themes() ) {
			return null;
		}

		// Strip the trailing slash from the request.
		$request->setPathInfo( \untrailingslashit( $request->getPathInfo() ) );

		try {
			$response = $this->router->dispatch( $request );
		} catch ( Throwable $e ) {
			// If no route found, allow the request to be passed down to WordPress.
			if ( $e instanceof ResourceNotFoundException && $this->router->should_pass_through_request( $request ) ) {
				return null;
			}

			$this->report_exception( $e );

			$response = $this->render_exception( $request, $e );
		}

		return $response;
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return void
	 */
	protected function report_exception( Throwable $e ) {
		$this->app[ Exception_Handler::class ]->report( $e );
	}

	/**
	 * Render the exception to a response.
	 *
	 * @param Request   $request Request instance.
	 * @param Throwable $e
	 * @return \Symfony\Component\HttpFoundation\Response|mixed
	 */
	protected function render_exception( $request, Throwable $e ) {
		return $this->app[ Exception_Handler::class ]->render( $request, $e );
	}
}
