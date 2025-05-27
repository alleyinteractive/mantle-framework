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
use Mantle\Http\View\Factory;
use Symfony\Component\HttpFoundation\Response as Symfony_Response;

use function Mantle\Support\Helpers\capture;

/**
 * Wrap the current response with a template.
 *
 * Passes the current response to a wrapper template. You can display the contents
 * with `render_main_template()`.
 */
class Wrap_Template {
	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( protected Application $app ) {}

	/**
	 * Handle an incoming request and setup the admin bar.
	 *
	 * @param Request  $request Request instance.
	 * @param \Closure $next Callback for the middleware.
	 */
	public function handle( Request $request, Closure $next ): mixed {
		if ( $request->is_json() ) {
			return $next( $request );
		}

		$response = $next( $request );

		// If the response is not a Response object, we can't wrap it.
		if ( ! $response instanceof \Symfony\Component\HttpFoundation\Response ) {
			return $response;
		}

		/**
		 * Filter the template for wrapping the content.
		 *
		 * @param string|null $template Template to use.
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
	 * If the theme supports block templates, we will use the block template parts
	 * from the theme.
	 *
	 * @param Symfony_Response $response Response object.
	 */
	protected function wrap_fallback( Symfony_Response $response ): Symfony_Response {
		// Attempt to wrap with HTML template parts from the theme.
		if ( current_theme_supports( 'block-templates' ) ) {
			// Add viewport meta tag.
			add_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );

			// Render title tag with content, regardless of whether theme has title-tag support.
			remove_action( 'wp_head', '_wp_render_title_tag', 1 );    // Remove conditional title tag rendering...
			add_action( 'wp_head', '_block_template_render_title_tag', 1 ); // ...and make it unconditional.

			$response->setContent(
				view( '@framework-views/wrapper', [ 'response' => $response ] ),
			);
		} else {
			// Fallback to the default header and footer.
			$response->setContent( capture( static function () use ( $response ): void {
				\get_header();
				// Assumed to be sanitized.
				echo $response->getContent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				\get_footer();
			} ) );
		}

		return $response;
	}
}
