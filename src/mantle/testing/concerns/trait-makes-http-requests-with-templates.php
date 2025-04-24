<?php
/**
 * Makes_Http_Requests_With_Templates trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Closure;
use InvalidArgumentException;

/**
 * Ensure that HTTP requests in unit tests load templates properly (such as
 * get_header()/get_footer()).
 *
 * Core's get_header() and get_footer() functions are not designed to be called
 * multiple times in the same request (they pass $load_once as true). This
 * doesn't allow for a proper test of page's response. This trait listens for
 * the relevant hook for header/footer/sidebar and loads the template if it hasn't
 * been loaded yet.
 *
 * @mixin Makes_Http_Requests
 */
trait Makes_Http_Requests_With_Templates {
	/**
	 * Storage of the loaded templates.
	 *
	 * @var array<string, bool>
	 */
	public static array $templates_loaded = [
		'header'  => false,
		'footer'  => false,
		'sidebar' => false,
	];

	/**
	 * Setup the trait and add the hooks to load templates.
	 */
	public function makes_http_requests_with_templates_set_up(): void {
		add_action( 'get_header', $this->generate_template_callback( 'header' ), 10, 2 );
		add_action( 'get_footer', $this->generate_template_callback( 'footer' ), 10, 2 );
		add_action( 'get_sidebar', $this->generate_template_callback( 'sidebar' ), 10, 2 );
	}

	/**
	 * Generate a callback to be passed to the pre-action for the given hook.
	 *
	 * @param string $hook The hook to generate the callback for.
	 */
	protected function generate_template_callback( string $hook ): Closure {
		return function ( mixed $name, mixed $args ) use ( $hook ): void {
			if ( ! isset( static::$templates_loaded[ $hook ] ) ) {
				throw new InvalidArgumentException(
					"Invalid template name: {$name}. Expected one of: " .
					implode( ', ', array_keys( static::$templates_loaded ) )
				);
			}

			// If the template was not loaded yet, mark it as loaded and bail.
			if ( ! static::$templates_loaded[ $hook ] ) {
				static::$templates_loaded[ $hook ] = true;

				return;
			}

			// From here on we mirror what get_header()/get_footer()/get_sidebar() do.
			$templates = [];
			$name      = (string) $name;

			if ( '' !== $name ) {
				$templates[] = "{$hook}-{$name}.php";
			}

			$templates[] = "{$hook}.php";

			locate_template( $templates, true, false, $args );
		};
	}
}
