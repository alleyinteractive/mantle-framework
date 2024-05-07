<?php
/**
 * Handler class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Exceptions;

use Exception;
use Mantle\Auth\Authentication_Error;
use Mantle\Contracts\Application;
use Mantle\Contracts\Exceptions\Handler as Contract;
use Mantle\Database\Model\Model_Not_Found_Exception;
use Mantle\Http\Request;
use Mantle\Http\Routing\Route;
use Mantle\Support\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
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
class Handler implements Contract {

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
		\Symfony\Component\Console\Exception\CommandNotFoundException::class,
		\Symfony\Component\Console\Exception\RuntimeException::class,
		Authentication_Error::class,
		HttpException::class,
		Model_Not_Found_Exception::class,
		ResourceNotFoundException::class,
	];

	/**
	 * Create a new exception handler instance.
	 *
	 * @param Application $container
	 */
	public function __construct( protected Application $container ) {}

	/**
	 * Report or log an exception.
	 *
	 * @param Throwable $e Exception thrown.
	 *
	 * @throws Exception Throws if logger not found.
	 */
	public function report( Throwable $e ): void {
		if ( $this->shouldnt_report( $e ) ) {
			return;
		}

		// Send the report method to the exception if it exists.
		$report_callable = [ $e, 'report' ];
		if ( is_callable( $report_callable ) ) {
			$this->container->call( $report_callable );
			return;
		}

		$logger = $this->container->make( LoggerInterface::class );

		$logger->error( $e->getMessage(), array_merge(
			$this->exception_context( $e ),
			$this->context(),
			[ 'exception' => $e ]
		) );
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
		} catch ( Throwable ) {
			return [];
		}
	}

	/**
	 * Render an exception into an HTTP response for the web.
	 *
	 * @param Request   $request Request object.
	 * @param Throwable $e Exception thrown.
	 * @return Response|mixed
	 * @throws \Throwable Thrown on catch.
	 */
	public function render( $request, Throwable $e ) {
		// Check if the exception has a render method.
		if ( method_exists( $e, 'render' ) ) {
			$response = $e->render( $request );

			if ( $response ) {
				return Route::ensure_response( $response );
			}
		}

		$e = $this->prepare_exception( $e );

		return $request->expects_json()
			? $this->prepare_json_response( $request, $e )
			: $this->prepare_http_response( $request, $e );
	}

	/**
	 * Render an exception to the console.
	 *
	 * @param OutputInterface $output
	 * @param Throwable       $e
	 *
	 * @throws Throwable Thrown in debug mode to trigger Whoops.
	 */
	public function render_for_console( OutputInterface $output, Throwable $e ): void {
		if ( config( 'app.debug' ) ) {
			// Use collision to render the exception if we're in debug mode.
			( new \NunoMaduro\Collision\Provider() )->register();

			throw $e;
		}

		( new \Mantle\Console\Application( $this->container ) )->render_throwable( $e, $output );
	}

	/**
	 * Prepare an exception for rendering.
	 *
	 * @param Throwable $e Exception thrown.
	 */
	protected function prepare_exception( Throwable $e ): Throwable {
		return match ( true ) {
			$e instanceof Model_Not_Found_Exception => new NotFoundHttpException( $e->getMessage(), $e, 404 ),
			$e instanceof ResourceNotFoundException => new NotFoundHttpException( $e->getMessage(), $e, 404 ),
			default => $e,
		};
	}

	/**
	 * Prepare a response for the given exception.
	 *
	 * @param  \Mantle\Http\Request $request Request object.
	 * @param  \Throwable           $e Exception thrown.
	 */
	protected function prepare_http_response( $request, Throwable $e ): Response {
		if ( ! $this->is_http_exception( $e ) && config( 'app.debug' ) ) {
			return $this->to_mantle_response(
				$this->convert_exception_to_response( $e ),
				$e,
			);
		}

		return $this->to_mantle_response(
			match ( config( 'app.debug' ) ) {
				true => $this->render_symfony_http_exception( $e ),
				default => $this->render_http_exception( $e ),
			},
			$e,
		);
	}

	/**
	 * Create a response for the given exception.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return Response
	 */
	protected function convert_exception_to_response( Throwable $e ): Response {
		if ( $e instanceof HttpException ) {
			return new Response(
				$this->render_http_exception( $e ),
				$e->getStatusCode(),
				$e->getHeaders(),
			);
		}

		return new Response( $this->render_http_exception( $e ), 500, [] );
	}

	/**
	 * Render the given exception with a view.
	 *
	 * Will attempt to load an error relative to the HTTP code. For example, a 500
	 * error will load `/views/error-500.php` that will fallback to '/views/error.php'
	 * if that is not found.
	 *
	 * This is not used when debugging is enabled.
	 *
	 * @param  Throwable $e Exception thrown.
	 * @todo Check if the view exists.
	 */
	protected function render_http_exception( Throwable $e ): Response {
		if ( config( 'app.debug' ) ) {
			return $this->render_symfony_http_exception( $e );
		}

		global $wp_query;

		// Calling a view this early doesn't work well for WordPress.
		if ( empty( $wp_query ) ) {
			$wp_query = new \WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		try {
			$view = $this->get_http_exception_view( $e );

			return response()->view(
				$view[0],
				$view[1],
				[
					'code'      => $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500,
					'exception' => $e,
				],
				$e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500,
			);
		} catch ( Throwable ) {
			// If there is an error rendering the view, fall back to the Symfony error renderer.
			return $this->render_symfony_http_exception( $e );
		}
	}

	/**
	 * Render the error with Symfony.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return Response
	 */
	protected function render_symfony_http_exception( Throwable $e ): Response {
		$renderer = ( new HtmlErrorRenderer( config( 'app.debug' ) ) )->render( $e );

		// Change the trace to skip a few frames
		// $trace = collect( $e->getTrace() );

		// //
		// dd($trace);

		return Route::ensure_response( $renderer->getAsString() );
	}

	/**
	 * Get the view used to render HTTP exceptions.
	 *
	 * @param Throwable $e Exception thrown.
	 * @return array{0: string, 1: string}
	 */
	protected function get_http_exception_view( Throwable $e ) {
		if ( $e instanceof HttpExceptionInterface ) {
			return [ 'error/error', (string) $e->getStatusCode() ];
		};

		return [ 'error/error', '500' ];
	}

	/**
	 * Map the given exception into an response.
	 *
	 * @param  Response   $response
	 * @param  \Throwable $e
	 * @return Response
	 */
	protected function to_mantle_response( Response $response, Throwable $e ) {
		if ( ! $response instanceof RedirectResponse ) {
			return new Response(
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
	 */
	protected function prepare_json_response( $request, Throwable | HttpException $e ): JsonResponse {
		return new JsonResponse(
			$this->convert_exception_to_array( $e ),
			$e instanceof HttpException ? $e->getStatusCode() : 500,
			$e instanceof HttpException ? $e->getHeaders() : []
		);
	}

	/**
	 * Convert the given exception to an array.
	 *
	 * @param  \Throwable $e
	 */
	protected function convert_exception_to_array( Throwable $e ): array {
		return config( 'app.debug' ) ? [
			'message'   => $e->getMessage(),
			'exception' => $e::class,
			'file'      => $e->getFile(),
			'line'      => $e->getLine(),
			'trace'     => collect( $e->getTrace() )->map(
				fn ( $trace) => Arr::except( $trace, [ 'args' ] )
			)->all(),
		] : [
			'message' => $this->is_http_exception( $e ) ? $e->getMessage() : __( 'Server Error', 'mantle' ),
		];
	}

	/**
	 * Determine if the given exception is an HTTP exception.
	 *
	 * @template TThrowable of Throwable
	 *
	 * @param Throwable $e Exception thrown.
	 * @phpstan-param TThrowable $e
	 * @phpstan-return (TThrowable is HttpException ? true : false)
	 */
	protected function is_http_exception( Throwable $e ): bool {
		return $e instanceof HttpException;
	}
}
