<?php
/**
 * Wrap_Template class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Middleware;

use Closure;
use Mantle\Contracts\Application;
use Mantle\Http\Request;
use Mantle\Http\Response;
use Mantle\Http\View\Factory;

/**
 * Wrap the current response with a template.
 *
 * Passes the current response to a wrapper template. You can display the contents
 * with `render_main_template()`.
 */
class Wrap_Template {
	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Handle an incoming request and setup the admin bar.
	 *
	 * @param Request  $request Request instance.
	 * @param \Closure $next Callback for the middleware.
	 * @return mixed
	 */
	public function handle( Request $request, Closure $next ) {
		if ( $request->is_json() ) {
			return $next( $request );
		}

		$response = $next( $request );

		/**
		 * Filter the template for wrapping the content.
		 *
		 * @param string $template Template to use.
		 */
		$template = \apply_filters( 'mantle_wrap_template', null );

		// Fill in the header and footer if no template is specified.
		if ( empty( $template ) ) {
			return $this->wrap_fallback( $response );
		}

		try {
			$factory = $this->app->make( Factory::class );
		} catch ( \Throwable $e ) {
			unset( $e );
			return $this->wrap_fallback( $response );
		}

		$response->setContent(
			$factory->make( $template, [ '_mantle_contents' => $response->getContent() ] )->render()
		);

		return $response;
	}

	/**
	 * Fallback to running get_header()/get_footer() around the content if a wrapper
	 * template is not specified.
	 *
	 * @param Response $response Response object.
	 * @return Response
	 */
	protected function wrap_fallback( $response ) {
		ob_start();
		\get_header();
		// Assumed to be sanitized.
		echo $response->getContent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		\get_footer();

		$response->setContent( ob_get_clean() );
		return $response;
	}
}
