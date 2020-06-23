<?php
/**
 * View_Loader class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\View;

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
	 * @var string[]
	 */
	protected $paths = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
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
			$this->add_path( ABSPATH . WPINC . '/theme-compat/' );
		}

		// Allow mantle-site to load views.
		$this->add_path( $this->base_path . '/views' );
	}

	/**
	 * Add a path to check against when loading a template.
	 *
	 * @param string $path Path to add.
	 * @return static
	 */
	public function add_path( string $path ) {
		$this->paths[] = $path;
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
		$templates = [];

		if ( $name ) {
			$templates[] = "{$slug}-{$name}.php";
		}

		$templates[] = "{$slug}.php";

		return $this->locate_template( $templates, true, false );
	}

	/**
	 * Locate the highest priority template file that exists in a set of templates.
	 *
	 * Acts as a replacement to `locate_template()`.
	 *
	 * @param array $templates Template files to search for.
	 * @param bool  $load If true, the template file will be loaded.
	 * @param bool  $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
	 * @return string The template filename if one is located.
	 */
	public function locate_template( array $templates, bool $load = true, bool $require_once = true ): string {
		$located = '';

		foreach ( $templates as $template ) {
			foreach ( $this->paths as $path ) {
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
