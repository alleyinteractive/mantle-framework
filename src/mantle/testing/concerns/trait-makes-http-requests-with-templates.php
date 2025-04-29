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
 * doesn't allow for the full response to be returned for a page in tests after
 * the first request. This trait listens for the relevant hook for
 * header/footer/sidebar and loads the template if it hasn't been loaded yet.
 *
 * @mixin Makes_Http_Requests
 */
trait Makes_Http_Requests_With_Templates {
	/**
	 * Storage of the loaded templates.
	 *
	 * @var array<string, bool>
	 */
	protected static array $templates_loaded = [
		'header'  => false,
		'footer'  => false,
		'sidebar' => false,
	];

	/**
	 * Setup the trait and add the hooks to load templates.
	 */
	public function makes_http_requests_with_templates_set_up(): void {
		foreach ( array_keys( static::$templates_loaded ) as $hook ) {
			add_action( "get_{$hook}", $this->generate_template_callback( $hook ), 10, 2 );
		}
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

			// From here on we mirror what get_{header,footer,sidebar}() does.
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
