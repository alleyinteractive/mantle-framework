<?php
/**
 * Handler class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Exceptions;

use Exception;
use Mantle\Auth\Authentication_Error;
use Mantle\Contracts\Container;
use Mantle\Contracts\Exceptions\Handler as Contract;
use Mantle\Database\Model\Model_Not_Found_Exception;
use Mantle\Http\Request;
use Mantle\Http\Routing\Route;
use Mantle\Support\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;

use function Mantle\Support\Helpers\collect;

/**
 * Error Handler for the Application
 *
 * Provides logging back to the logging service provider for exceptions thrown and
 * graceful handling of errors.
 */
abstract class Handler implements Contract {

	/**
	 * The container implementation.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * A list of the exception types that are not reported.
	 *
	 * @var array
	 */
	protected $dont_report = [];

	/**
	 * A list of the internal exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $internal_dont_report = [
		Authentication_Error::class,
		HttpException::class,
		Model_Not_Found_Exception::class,
		ResourceNotFoundException::class,
	];

	/**
	 * Create a new exception handler instance.
	 *
	 * @param Container $container
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Report or log an exception.
	 *
	 * @param Throwable $e Exception thrown.
	 *
	 * @throws Exception Throws if logger not found.
	 */
	public function report( Throwable $e ) {
		if ( $this->shouldnt_report( $e ) ) {
			return;
		}

		// Send the report method to the exception if it exists.
		$report_callable = [ $e, 'report' ];
		if ( is_callable( $report_callable ) ) {
			$this->container->call( $report_callable );
			return;
		}

		try {
			$logger = $this->container->make( LoggerInterface::class );
		} catch ( Exception $e ) {
			throw $e;
		}

		$logger->error(
			$e->getMessage(),
			array_merge(
				$this->exception_context( $e ),
				$this->context(),
				[ 'exception' => $e ]
			)
		);
	}

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param  \Throwable $e
	 * @return bool
	 */
	public function should_report( Throwable $e ) {
		return ! $this->shouldnt_report( $e );
	}

	/**
	 * Determine if the exception is in the "do not report" list.
	 *
	 * @param  \Throwable $e
	 * @return bool
	 */
	protected function shouldnt_report( Throwable $e ) {
		$dont_report = array_merge( $this->dont_report, $this->internal_dont_report );

		return ! is_null(
			Arr::first( $dont_report, fn ( $type ) => $e instanceof $type ),
		);
	}

	/**
	 * Get the default exception context variables for logging.
	 *
	 * @param  \Throwable $e
	 * @return array
	 */
	protected function exception_context( Throwable $e ) {
		if ( method_exists( $e, 'context' ) ) {
			return $e->context();
		}

		return [];
	}

	/**
	 * Get the default context variables for logging.
	 *
	 * @return array
	 */
	protected function context() {
		try {
			return array_filter(
				[
					'blogId' => get_current_blog_id(),
					'userId' => get_current_user_id(),
				]
			);
		} catch ( Throwable $e ) {
			return [];
		}
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param Request   $request Request object.
	 * @param Throwable $e Exception thrown.
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Throwable Thrown on catch.
	 */
	public function render( $request, Throwable $e ) {
		// Check if the exception has a render method.
		if ( method_exists( $e, 'render' ) ) {
			$response = $e->render( $request );

			if ( $response ) {
				return Route::ensure_response( $request, $response );
			}
		}

		// Display the entire exception if the app is running in console mode.
		if ( method_exists( $this->container, 'is_running_in_console' ) && $this->container->is_running_in_console() ) {
			dump( $e );
			return '';
		}

		/**
		 * Allow the whoops page handler to handle this automatically except for
		 * JSON requests which returns an error in JSON instead.
		 *
		 * @see Mantle\Framework\Providers\Error_Service_Provider
		 */
		if ( config( 'app.debug' ) && ! $request->expects_json() ) {
			throw $e;
		}

		$e = $this->prepare_exception( $e );

		return $request->expects_json()
			? $this->prepare_json_response( $request, $e )
			: $this->prepare_response( $request, $e );
	}

	/**
	 * Prepare an exception for rendering.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return Throwable
	 */
	protected function prepare_exception( Throwable $e ): Throwable {
		if ( $e instanceof Model_Not_Found_Exception ) {
			$e = new NotFoundHttpException( $e->getMessage(), $e, 404 );
		}

		return $e;
	}

	/**
	 * Prepare a response for the given exception.
	 *
	 * @param  \Mantle\Http\Request $request Request object.
	 * @param  \Throwable           $e Exception thrown.
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function prepare_response( $request, Throwable $e ) {
		if ( $e instanceof ResourceNotFoundException ) {
			$e = new NotFoundHttpException( $e->getMessage(), $e, 404 );
		} elseif ( ! $this->is_http_exception( $e ) ) {
			$e = new HttpException( 500, $e->getMessage() );
		}

		return $this->to_mantle_response(
			$this->render_http_exception( $e ),
			$e
		);
	}

	/**
	 * Render the given HttpException with a view.
	 *
	 * Will attempt to load an error relative to the HTTP code. For example, a 500
	 * error will load `/views/error-500.php` that will fallback to '/views/error.php'
	 * if that is not found.
	 *
	 * @param  \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @todo Check if the view exists.
	 */
	protected function render_http_exception( HttpExceptionInterface $e ) {
		global $wp_query;

		// Calling a view this early doesn't work well for WordPress.
		if ( empty( $wp_query ) ) {
			$wp_query = new \WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$view = $this->get_http_exception_view( $e );
		return Route::ensure_response(
			response()->view(
				$view[0],
				$view[1],
				[
					'code'      => $e->getStatusCode(),
					'exception' => $e,
				],
				$e->getStatusCode(),
			) ?: 'An error has occurred.'
		);
	}

	/**
	 * Get the view used to render HTTP exceptions.
	 *
	 * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e Exception thrown.
	 * @return array
	 */
	protected function get_http_exception_view( HttpExceptionInterface $e ) {
		return [ 'error/error', $e->getStatusCode() ];
	}

	/**
	 * Map the given exception into an response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response $response
	 * @param  \Throwable                                 $e
	 * @return \Mantle\Http\Response
	 */
	protected function to_mantle_response( $response, Throwable $e ) {
		if ( ! $response instanceof RedirectResponse ) {
			$response = new Response(
				$response->getContent(),
				$response->getStatusCode(),
				$response->headers->all()
			);
		}

		return $response;
	}

	/**
	 * Prepare a JSON response for the given exception.
	 *
	 * @param Request                 $request
	 * @param Throwable|HttpException $e
	 * @return JsonResponse
	 */
	protected function prepare_json_response( $request, Throwable | HttpException $e ): JsonResponse {
		return new JsonResponse(
			$this->convert_exception_to_array( $e ),
			$this->is_http_exception( $e ) ? $e->getStatusCode() : 500,
			$this->is_http_exception( $e ) ? $e->getHeaders() : []
		);
	}

	/**
	 * Convert the given exception to an array.
	 *
	 * @param  \Throwable $e
	 * @return array
	 */
	protected function convert_exception_to_array( Throwable $e ): array {
		return config( 'app.debug' ) ? [
			'message'   => $e->getMessage(),
			'exception' => get_class( $e ),
			'file'      => $e->getFile(),
			'line'      => $e->getLine(),
			'trace'     => collect( $e->getTrace() )->map(
				function ( $trace ) {
					return Arr::except( $trace, [ 'args' ] );
				}
			)->all(),
		] : [
			'message' => $this->is_http_exception( $e ) ? $e->getMessage() : __( 'Server Error', 'mantle' ),
		];
	}

	/**
	 * Determine if the given exception is an HTTP exception.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return bool
	 */
	protected function is_http_exception( Throwable $e ): bool {
		return $e instanceof HttpException;
	}
}
