<?php
/**
 * View_Loader class file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.WP.DiscouragedConstants
 */

namespace Mantle\Framework\Http\View;

use InvalidArgumentException;
use Mantle\Framework\Support\Collection;
use Mantle\Framework\Support\Str;

use function Mantle\Framework\Helpers\collect;

/**
 * View Loader
 * Handles the loading of a template file in any location of the code base.
 */
class View_Loader {
	/**
	 * Base path for the application.
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * Paths to check against when loading a template.
	 *
	 * @var array
	 */
	protected $paths = [];

	/**
	 * Constructor.
	 *
	 * @param string $base_path Base path.
	 */
	public function __construct( string $base_path ) {
		$this->base_path = $base_path;

		$this->set_default_paths();
	}

	/**
	 * Set the default paths to load from for WordPress sites.
	 */
	public function set_default_paths() {
		if ( defined( 'STYLESHEETPATH' ) && STYLESHEETPATH ) {
			$this->add_path( STYLESHEETPATH );
		}

		if ( defined( 'TEMPLATEPATH' ) && TEMPLATEPATH ) {
			$this->add_path( TEMPLATEPATH );
		}

		if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) {
			$this->add_path( ABSPATH . WPINC . '/theme-compat' );
		}

		// Allow mantle-site to load views.
		$this->add_path( $this->base_path . '/views' );
	}

	/**
	 * Add a path to check against when loading a template.
	 *
	 * @param string $path Path to add.
	 * @param string $alias Alias to set it as, defaults to none.
	 * @return static
	 *
	 * @throws InvalidArgumentException Thrown on invalid alias.
	 */
	public function add_path( string $path, string $alias = null ) {
		if ( $alias && Str::contains( $alias, [ '/', '\\', '@' ] ) ) {
			throw new InvalidArgumentException( 'Alias cannot contain invalid characters.' );
		}

		if ( $alias ) {
			$this->paths[ $alias ] = \untrailingslashit( $path );
		} else {
			$this->paths[] = \untrailingslashit( $path );
		}

		return $this;
	}

	/**
	 * Remove a path to check against when loading a template.
	 *
	 * @param string $path Path to remove.
	 * @return static
	 */
	public function remove_path( string $path ) {
		$index = array_search( $path, $this->paths, true );
		if ( false !== $index ) {
			unset( $this->paths[ $index ] );
		}

		return $this;
	}

	/**
	 * Get the registered paths.
	 *
	 * @return array
	 */
	public function get_paths(): array {
		return array_unique( $this->paths );
	}

	/**
	 * Remove all paths to check against.
	 *
	 * @return static
	 */
	public function clear_paths() {
		$this->paths = [];
		return $this;
	}

	/**
	 * Load a template by template name.
	 *
	 * Acts as a replacement to `get_template_part()` to allow sites to load templates
	 * outside of a theme.
	 *
	 * @param string $slug Template slug.
	 * @param string $name Template name.
	 * @return string The template filename if one is located.
	 */
	public function load( string $slug, string $name = null ): string {
		$alias = null;

		// Extract the alias if passed.
		if ( 0 === strpos( $slug, '@' ) ) {
			$alias = substr( Str::before( $slug, '/' ), 1 );
			$slug  = Str::after( $slug, '/' );
		}

		$templates = [];

		if ( $name ) {
			$templates[] = "{$slug}-{$name}.php";
		}

		$templates[] = "{$slug}.php";

		return $this->locate_template( $templates, true, false, $alias );
	}

	/**
	 * Locate the highest priority template file that exists in a set of templates.
	 *
	 * Acts as a replacement to `locate_template()`.
	 *
	 * @param array  $templates Template files to search for.
	 * @param bool   $load If true, the template file will be loaded.
	 * @param bool   $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
	 * @param string $alias Alias to load, optional.
	 * @return string The template filename if one is located.
	 */
	public function locate_template( array $templates, bool $load = true, bool $require_once = true, string $alias = null ): string {
		$located = '';

		foreach ( $templates as $template ) {
			foreach ( $this->get_paths() as $key => $path ) {
				// Confirm the alias if passed.
				if ( $alias && $key !== $alias ) {
					continue;
				}

				$file_path = $path . '/' . $template;

				if ( file_exists( $file_path ) ) {
					$located = $file_path;
					break 2;
				}
			}
		}

		if ( $load && ! empty( $located ) ) {
			\load_template( $located, $require_once );
		}

		return $located;
	}
}
