<?php
/**
 * Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\View;

use Illuminate\View\Concerns\ManagesLayouts;
use Illuminate\View\Concerns\ManagesLoops;
use Illuminate\View\Concerns\ManagesStacks;
use InvalidArgumentException;
use Mantle\Contracts\Container;
use Mantle\Contracts\Http\View\Factory as Contract;
use Mantle\Contracts\View\Engine;
use Mantle\Support\Arr;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use Mantle\View\Engines\Engine_Resolver;
use WP_Query;

/**
 * View Factory
 */
class Factory implements Contract {
	use ManagesLayouts;
	use ManagesLoops;
	use ManagesStacks;

	/**
	 * The IoC container instance.
	 */
	protected Container $container;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array<mixed>
	 */
	protected array $shared = [];

	/**
	 * Stack of views being rendered.
	 *
	 * @var array<mixed>
	 */
	protected array $stack;

	/**
	 * Current view being rendered.
	 */
	protected ?View $current = null;

	/**
	 * The cached array of engines for file paths.
	 *
	 * @var array<string, string>
	 */
	protected array $path_engine_cache = [];

	/**
	 * The extension to engine bindings.
	 *
	 * @var string[]
	 */
	protected array $extensions = [
		'blade.php' => 'blade',
		'php'       => 'php',
		'css'       => 'file',
		'html'      => 'file',
	];

	/**
	 * Constructor.
	 *
	 * @param Container       $container Container to set.
	 * @param Engine_Resolver $engines Engine Resolver.
	 * @param View_Finder     $finder View Finder.
	 */
	public function __construct( Container $container, protected Engine_Resolver $engines, protected View_Finder $finder ) {
		$this->set_container( $container );
		$this->share( '__env', $this );
	}

	/**
	 * Set the container to use.
	 *
	 * @param Container $container Container instance.
	 */
	public function set_container( Container $container ): static {
		$this->container = $container;
		return $this;
	}

	/**
	 * Get the container to use.
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Get the current view.
	 */
	public function get_current(): ?View {
		return $this->current;
	}

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param array<string, mixed>|string $key Key to share.
	 * @param mixed|null                  $value Value to share.
	 */
	public function share( array|string $key, mixed $value = null ): void {
		$keys = is_array( $key ) ? $key : [ $key => $value ];

		$this->shared = array_merge( $this->shared, $keys );
	}

	/**
	 * Get an item from the shared data.
	 *
	 * @param string $key Key to get item by.
	 * @param mixed  $default Default value.
	 */
	public function shared( string $key, mixed $default = null ): mixed {
		return Arr::get( $this->shared, $key, $default );
	}

	/**
	 * Get all of the shared data for the environment.
	 *
	 * @return array<string, mixed>
	 */
	public function get_shared(): array {
		return $this->shared;
	}

	/**
	 * Push a view onto the stack and set it as the current view.
	 *
	 * @param View $view View being loaded.
	 */
	public function push( View $view ): static {
		$this->stack[] = $view;
		$this->current = $view;

		return $this;
	}

	/**
	 * Pop a partial off the top of the stack and set the current partial to the
	 * next one down.
	 */
	public function pop(): static {
		array_pop( $this->stack );

		$this->current = end( $this->stack ) ?: null;

		if ( ! $this->current instanceof View ) {
			$this->current = null;
		}

		return $this;
	}

	/**
	 * Get a variable from the current view.
	 *
	 * @param string $key Variable to get.
	 * @param mixed  $default Default value if unset.
	 */
	public function get_var( string $key, mixed $default = null ): mixed {
		if ( empty( $this->current ) ) {
			return $default;
		}

		return $this->current->get_variable( $key, $default );
	}

	/**
	 * Get the rendered contents of a view.
	 *
	 * @param string                           $slug View slug.
	 * @param array<string, mixed>|string|null $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @param array<string, mixed>             $variables Variables for the view, optional.
	 */
	public function make( string $slug, array|string|null $name = null, array $variables = [] ): View {
		if ( is_array( $name ) ) {
			$variables = array_merge( $name, $variables );
			$name      = null;
		}

		$path = $this->resolve_view_path( $slug, $name );

		return new View(
			factory: $this,
			engine: $this->get_engine_from_path( $path ),
			path: $path,
			data: array_merge( $this->get_shared(), $variables ),
		);
	}

	/**
	 * Resolve the view path for a given template slug and name.
	 *
	 * @param string $slug Template slug.
	 * @param string $name Template name.
	 * @return string|null File path, null otherwise.
	 */
	protected function resolve_view_path( string $slug, ?string $name = null ): ?string {
		// Prepend the current view if the requested slug is a child template.
		if ( Str::starts_with( $slug, '_' ) && $this->current instanceof \Mantle\Http\View\View ) {
			return $this->resolve_child_view_path_from_parent( $slug );
		}

		return $this->finder->find( $slug, $name );
	}

	/**
	 * Resolve a child view path from the current parent.
	 *
	 * @param string $slug Slug of the view to load.
	 * @return string
	 * @throws InvalidArgumentException Thrown if child view not found.
	 */
	protected function resolve_child_view_path_from_parent( string $slug ) {
		$path = Str::before( $this->current->get_path(), '.' ) . '-' . Str::substr( $slug, 1 );

		foreach ( $this->finder->get_possible_view_files( $path ) as $file ) {
			if ( file_exists( $file ) ) {
				return $file;
			}
		}

		throw new InvalidArgumentException( "Child view not found: [{$path}]" );
	}

	/**
	 * Create a collection of views that loop over a collection of WordPress objects.
	 *
	 * While iterating over the data, the proper post data is setup for each item.
	 *
	 * @param array<mixed>|\ArrayAccess<array-key, mixed> $data Array of WordPress data to loop over.
	 * @param string                                      $slug View slug.
	 * @param array<mixed>|string                         $name View name, optional. Supports passing variables in if
	 *                                                   $variables is not used.
	 * @param array<mixed>                                $variables Variables for the view, optional.
	 */
	public function loop( $data, string $slug, $name = null, array $variables = [] ): Collection {
		$results = new Collection();

		// Extract the posts from the query.
		if ( $data instanceof WP_Query ) {
			$data = $data->posts;
		}

		// Loop through an array of posts.
		foreach ( $data as $i => $item ) {
			// Append the current index as a dynamic variable.
			$variables['index'] = $i;

			$results[] = $this->make( $slug, $name, $variables )->set_post( $item );
		}

		return $results;
	}

	/**
	 * Iterate over an array, loading a given template part for each item in the
	 * array.
	 *
	 * @param array<mixed>|\ArrayAccess $data Array of data to iterate over over.
	 * @param string                    $slug View slug.
	 * @param array<mixed>|string       $name View name, optional. Supports passing variables in if
	 *                                 $variables is not used.
	 * @param array<string, mixed>      $variables Variables for the view, optional.
	 */
	public function iterate( $data, string $slug, $name = null, array $variables = [] ): Collection {
		if ( is_array( $name ) ) {
			$variables = array_merge( $name, $variables );
			$name      = null;
		}

		$results = new Collection();

		foreach ( $data as $index => $item ) {
			$variables['item']  = $item;
			$variables['index'] = $index;

			$results[] = $this->make( $slug, $name, $variables );
		}

		return $results;
	}

	/**
	 * Resolve the engine for a given path.
	 *
	 * @param string $path Path to resolve.
	 *
	 * @throws InvalidArgumentException Thrown on unknown extension from file.
	 */
	public function get_engine_from_path( string $path ): \Mantle\Contracts\View\Engine {
		if ( isset( $this->path_engine_cache[ $path ] ) ) {
			return $this->engines->resolve( $this->path_engine_cache[ $path ] );
		}

		$extension = $this->get_extension( $path );
		if ( ! $extension ) {
			throw new InvalidArgumentException( "Unknown extension in file: {$path}" );
		}

		$this->path_engine_cache[ $path ] = $this->extensions[ $extension ];

		return $this->engines->resolve( $this->extensions[ $extension ] );
	}

	/**
	 * Get the extension used by the view file.
	 *
	 * @param  string $path Path to check against.
	 */
	protected function get_extension( string $path ): ?string {
		$extensions = array_keys( $this->extensions );

		return Arr::first(
			$extensions,
			fn ( $value ) => Str::ends_with( $path, '.' . $value )
		);
	}
}
