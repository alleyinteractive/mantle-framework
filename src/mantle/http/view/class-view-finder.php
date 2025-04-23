<?php
/**
 * View_Finder class file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.WP.DiscouragedConstants
 */

namespace Mantle\Http\View;

use InvalidArgumentException;
use Mantle\Filesystem\Filesystem;
use Mantle\Support\Str;

use function Mantle\Support\Helpers\event;

/**
 * View Finder
 *
 * Handles the flexible location of templates.
 */
class View_Finder {
	/**
	 * Paths to check against when loading a template.
	 *
	 * @var string[]
	 */
	protected array $paths = [];

	/**
	 * Register a view extension with the finder.
	 *
	 * @var string[]
	 */
	protected array $extensions = [
		'blade.php',
		'php',
		'css',
		'html',
	];

	/**
	 * Constructor.
	 *
	 * @param string     $base_path Base path.
	 * @param Filesystem $files Filesystem instance.
	 */
	public function __construct( protected string $base_path, protected readonly Filesystem $files ) {
		$this->set_default_paths();

		\add_action( 'after_setup_theme', [ $this, 'set_default_paths' ] );
		\add_action( 'switch_theme', [ $this, 'set_default_paths' ] );
	}

	/**
	 * Register an extension with the view finder.
	 *
	 * @param string $extension Extension to add.
	 */
	public function add_extension( string $extension ): static {
		$index = array_search( $extension, $this->extensions, true );

		if ( false !== $index ) {
			unset( $this->extensions[ $index ] );
		}

		array_unshift( $this->extensions, $extension );

		return $this;
	}

	/**
	 * Get registered extensions.
	 *
	 * @return string[]
	 */
	public function get_extensions(): array {
		return $this->extensions;
	}

	/**
	 * Set the default paths to load from for WordPress sites.
	 */
	public function set_default_paths(): void {
		if ( function_exists( 'get_stylesheet_directory' ) ) {
			$this->add_path( get_stylesheet_directory(), 'stylesheet-path' );
			$this->add_path( get_template_directory(), 'template-path' );
		}

		if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) {
			$this->add_path( ABSPATH . WPINC . '/theme-compat', 'theme-compat' );
		}

		// Allow mantle-site to load views.
		$this->add_path( $this->base_path . '/views', 'mantle-site' );

		/**
		 * Dispatched when the view finder is setting its default paths.
		 *
		 * @param View_Finder $view_finder View finder instance.
		 */
		event( 'mantle_view_finder_paths', $this );
	}

	/**
	 * Add a path to check against when loading a template.
	 *
	 * @param string $path Path to add.
	 * @param string $alias Alias to set it as, defaults to none.
	 *
	 * @throws InvalidArgumentException Thrown on invalid alias.
	 */
	public function add_path( string $path, ?string $alias = null ): static {
		if ( $alias && Str::contains( $alias, [ '/', '\\', '@' ] ) ) {
			throw new InvalidArgumentException( 'Alias cannot contain invalid characters.' );
		}

		$path = Str::untrailing_slash( $path );

		if ( $alias ) {
			$this->paths[ $alias ] = $path;
		} elseif ( ! in_array( $path, $this->paths, true ) ) {
			$this->paths[] = $path;
		}

		return $this;
	}

	/**
	 * Remove a path to check against when loading a template.
	 *
	 * @param string $path Path to remove.
	 */
	public function remove_path( string $path ): static {
		$index = array_search( $path, $this->paths, true );
		if ( false !== $index ) {
			unset( $this->paths[ $index ] );
		}

		return $this;
	}

	/**
	 * Get the registered paths.
	 *
	 * @return string[]
	 */
	public function get_paths(): array {
		return array_unique( $this->paths );
	}

	/**
	 * Remove all paths to check against.
	 */
	public function clear_paths(): static {
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
	public function find( string $slug, ?string $name = null ): string {
		$alias = null;

		// Extract the alias if passed.
		if ( $this->has_hint_information( $slug ) ) {
			[ $alias, $slug ] = explode( '/', $slug, 2 );
		}

		$templates = [];

		if ( $name ) {
			$templates[] = "{$slug}-{$name}";
		}

		$templates[] = $slug;

		return $this->locate_template( $templates, $alias );
	}

	/**
	 * Locate the highest priority template file that exists in a set of templates.
	 *
	 * Acts as a replacement to `locate_template()`.
	 *
	 * @param string[] $templates Template files to search for.
	 * @param string   $alias Alias to load, optional.
	 * @return string The template filename if one is located.
	 *
	 * @throws InvalidArgumentException Thrown on unknown view to locate.
	 */
	protected function locate_template( array $templates, ?string $alias = null ): string {
		$paths = $this->get_paths();

		if ( $alias ) {
			$paths = array_filter(
				$paths,
				fn ( $path_alias ) => $alias === $path_alias,
				ARRAY_FILTER_USE_KEY
			);
		}

		foreach ( $templates as $template ) {
			$possible_view_files = $this->get_possible_view_files( $template );

			foreach ( $possible_view_files as $possible_view_file ) {
				foreach ( $this->get_paths() as $path ) {
					$path = "{$path}/{$possible_view_file}";

					if ( $this->files->exists( $path ) ) {
						return $path;
					}
				}
			}
		}

		throw new InvalidArgumentException( "View [{$templates[0]}] not found." );
	}

	/**
	 * Calculate the possible view file paths with supported extensions.
	 *
	 * @param string $name File path without extension.
	 * @return string[]
	 */
	public function get_possible_view_files( string $name ): array {
		return array_map(
			fn ( $extension ) => "{$name}.{$extension}",
			$this->extensions
		);
	}

	/**
	 * Check if the view name has hint information.
	 *
	 * The hint information is used to determine if the view should be loaded from
	 * a specific path that is passed in the format of "@alias/view-name".
	 *
	 * @param string $name View name.
	 */
	public function has_hint_information( string $name ): bool {
		return str_starts_with( $name, '@' ) && false !== strpos( $name, '/' );
	}
}
