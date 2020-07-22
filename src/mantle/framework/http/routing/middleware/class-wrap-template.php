<?php
/**
 * Wrap_Template class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing\Middleware;

use Closure;
use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Http\Request;
use Mantle\Framework\Http\View\Factory;
use Mantle\Framework\Query_Monitor\Query_Monitor_Service_Provider;

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
		$response = $next( $request );

		/**
		 * Filter the template for wrapping the content.
		 *
		 * @param string $template Template to use.
		 */
		$template = \apply_filters( 'mantle_wrap_template', 'wrapper' );

		// Bail if the template is invalid.
		if ( empty( $template ) ) {
			return $response;
		}

		try {
			$factory = $this->app->make( Factory::class );
		} catch ( \Throwable $e ) {
			unset( $e );
			return $response;
		}

		$response->setContent(
			$factory->make( $template, [ '_mantle_contents' => $response->getContent() ] )->render()
		);

		return $response;
	}
}
