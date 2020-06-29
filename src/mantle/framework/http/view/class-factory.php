<?php
/**
 * Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\View;

use Mantle\Framework\Contracts\Container;
use Mantle\Framework\Contracts\Http\View\Factory as ViewFactory;
use Mantle\Framework\Support\Arr;
use Mantle\Framework\Support\Collection;
use WP_Query;

/**
 * View Factory
 */
class Factory implements ViewFactory {
	/**
	 * The IoC container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = [];

	/**
	 * Stack of views being rendered.
	 *
	 * @var array
	 */
	protected $stack;

	/**
	 * Current view being rendered.
	 *
	 * @var View
	 */
	protected $current;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container to set.
	 */
	public function __construct( Container $container ) {
		$this->set_container( $container );
		$this->share( '__env', $this );
	}

	/**
	 * Set the container to use.
	 *
	 * @param Container $container Container instance.
	 */
	public function set_container( Container $container ) {
		$this->container = $container;
		return $this;
	}

	/**
	 * Get the container to use.
	 *
	 * @return Container
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Get the current view.
	 *
	 * @return View|null
	 */
	public function get_current(): ?View {
		return $this->current;
	}

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param array|string $key Key to share.
	 * @param mixed|null   $value Value to share.
	 * @return mixed
	 */
	public function share( $key, $value = null ) {
		$keys = is_array( $key ) ? $key : [ $key => $value ];

		foreach ( $keys as $key => $value ) {
			$this->shared[ $key ] = $value;
		}

		return $value;
	}

	/**
	 * Get an item from the shared data.
	 *
	 * @param string $key Key to get item by.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function shared( $key, $default = null ) {
		return Arr::get( $this->shared, $key, $default );
	}

	/**
	 * Get all of the shared data for the environment.
	 *
	 * @return array
	 */
	public function get_shared(): array {
		return $this->shared;
	}

	/**
	 * Push a view onto the stack and set it as the current view.
	 *
	 * @param View $view View being loaded.
	 * @return static
	 */
	public function push( View $view ) {
		$this->stack[] = $view;
		$this->current = $view;
		return $this;
	}

	/**
	 * Pop a partial off the top of the stack and set the current partial to the
	 * next one down.
	 *
	 * @return static
	 */
	public function pop() {
		array_pop( $this->stack );
		$this->current = end( $this->stack );

		if ( ! $this->current ) {
			$this->current = null;
		}

		return $this;
	}

	/**
	 * Get a variable from the current view.
	 *
	 * @param string $key Variable to get.
	 * @param mixed  $default Default value if unset.
	 * @return mixed
	 */
	public function get_var( string $key, $default = null ) {
		if ( empty( $this->current ) ) {
			return $default;
		}

		return $this->current->get_variable( $key, $default );
	}

	/**
	 * Get the rendered contents of a view.
	 *
	 * @param string       $slug View slug.
	 * @param array|string $name View name, optional. Supports passing variables in if
	 *                           $variables is not used.
	 * @param array        $variables Variables for the view, optional.
	 * @return View
	 */
	public function make( string $slug, $name = null, array $variables = [] ): View {
		if ( is_array( $name ) ) {
			$variables = array_merge( $name, $variables );
			$name      = null;
		}

		$variables = array_merge( $this->get_shared(), $variables );

		return new View( $this, $slug, $name, $variables );
	}

	/**
	 * Create a collection of views that loop over a collection of WordPress objects.
	 *
	 * While iterating over the data, the proper post data is setup for each item.
	 *
	 * @param array|\ArrayAccess $data Array of WordPress data to loop over.
	 * @param string             $slug View slug.
	 * @param array|string       $name View name, optional. Supports passing variables in if
	 *                                 $variables is not used.
	 * @param array              $variables Variables for the view, optional.
	 * @return Collection
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
	 * @param array|\ArrayAccess $data Array of data to iterate over over.
	 * @param string             $slug View slug.
	 * @param array|string       $name View name, optional. Supports passing variables in if
	 *                                 $variables is not used.
	 * @param array              $variables Variables for the view, optional.
	 * @return Collection
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
}
