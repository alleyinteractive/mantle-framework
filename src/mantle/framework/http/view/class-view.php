<?php
/**
 * View class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\View;

use Mantle\Framework\Contracts\Http\View\Factory as Factory_Contract;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Support\Arr;

/**
 * View Class
 */
class View {
	/**
	 * View Factory
	 *
	 * @var Factory
	 */
	protected $factory;

	/**
	 * View slug.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * View name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * View parent name.
	 *
	 * @var array
	 */
	protected $parent;

	/**
	 * Array of view data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Post object to set for the post.
	 *
	 * @var Post|\WP_Post|int
	 */
	protected $post;

	/**
	 * The original post to restore after rendering the view.
	 *
	 * @var \WP_Post
	 */
	public $original_post;

	/**
	 * Constructor.
	 *
	 * @param Factory_Contract $factory View Factory.
	 * @param string           $slug View slug.
	 * @param array|string     $name View name, optional. Supports passing variables in if
	 *                               $variables is not used.
	 * @param array            $variables Variables for the view, optional.
	 */
	public function __construct( Factory_Contract $factory, string $slug, $name = null, array $variables = [] ) {
		$this->factory = $factory;
		$this->slug    = $slug;

		if ( is_array( $name ) ) {
			$variables = array_merge( $name, $variables );
		} else {
			$this->name = $name;
		}

		$this->data = $variables;
	}

	/**
	 * Get the view slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the view name.
	 *
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->name ?? null;
	}

	/**
	 * Set the parent for the view.
	 *
	 * @param array $parent Parent file.
	 * @return static
	 */
	public function set_parent( array $parent = null ) {
		$this->parent = $parent;

		// If the slug starts with an underscores, it's a sub-partial.
		// Determine the proper slug name from the parent.
		if ( $parent && '_' === substr( $this->slug, 0, 1 ) ) {
			$this->set_slug_from_parent();
		}

		return $this;
	}

	/**
	 * Set the post for the view.
	 *
	 * Allows the global WordPress post object to be adjusted when rendering the view.
	 *
	 * @param Post|\WP_Post|int $post Post object.
	 * @return static
	 */
	public function set_post( $post ) {
		$this->post = $post;
		return $this;
	}

	/**
	 * Add a piece of data to the view.
	 *
	 * @param string|array $key Key to set.
	 * @param mixed        $value Value to set.
	 * @return static
	 */
	public function with( $key, $value = null ) {
		if ( is_array( $key ) ) {
			$this->data = array_merge( $this->data, $key );
		} else {
			Arr::set( $this->data, $key, $value );
		}

		return $this;
	}

	/**
	 * Get the data for the view.
	 *
	 * @return array
	 */
	public function get_variables(): array {
		return $this->data;
	}

	/**
	 * Get a specific variable for the view.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default Default value, optional.
	 * @return mixed
	 */
	public function get_variable( string $key, $default = null ) {
		return Arr::get( $this->data, $key, $default );
	}

	/**
	 * Set the view parent from the current item in the view factory.
	 */
	protected function set_parent_from_current() {
		$current = $this->factory->get_current();

		if ( $current ) {
			$this->set_parent( [ $current->get_slug(), $current->get_name() ] );
		} else {
			$this->set_parent( null );
		}
	}

	/**
	 * Set the slug relative to the parent file.
	 */
	protected function set_slug_from_parent() {
		if ( ! $this->parent ) {
			return;
		}

		$this->slug = $this->get_parent_base() . str_replace( '_', '-', $this->slug );
	}

	/**
	 * Determine the parent file that was actually loaded.
	 *
	 * `get_template_part()` doesn't let us know if the `$name` argument had any
	 * impact in loading the file. Therefore, we need to first identify the
	 * parent to always correctly load a sub-partial.
	 *
	 * @return string
	 */
	protected function get_parent_base(): string {
		// If there's no $name, we can keep it simple.
		if ( empty( $this->parent[1] ) ) {
			return $this->parent[0];
		}

		// Locate the parent template that was loaded.
		$templates = [];
		$name      = (string) $this->parent[1];
		if ( '' !== $name ) {
			$templates[] = "{$this->parent[0]}-{$name}.php";
		}
		$templates[] = "{$this->parent[0]}.php";
		$located     = locate_template( $templates, false );

		// If we have a located template, and it contains the $name, return it.
		if ( $located && false !== strpos( $located, "{$this->parent[0]}-{$name}.php" ) ) {
			return "{$this->parent[0]}-{$name}";
		}

		// Otherwise, just return the parent's slug.
		return $this->parent[0];
	}

	/**
	 * Set the global post object for the view.
	 */
	protected function setup_post_object() {
		global $post;

		if ( ! isset( $this->post ) ) {
			return;
		}

		if ( $this->post instanceof Post ) {
			$post = \get_post( $this->post->id() );
		} else {
			$post = \get_post( $this->post );
		}

		\setup_postdata( $post );
	}

	/**
	 * Backup the current global `$post`.
	 */
	protected function preserve_post() {
		if ( ! empty( $GLOBALS['post'] ) ) {
			$this->original_post = $GLOBALS['post'];
		} else {
			$this->original_post = null;
		}
	}

	/**
	 * Restore the backup of the global $post.
	 *
	 * If our template part changed the global post, we reset it to what it was
	 * before loading the template part. Note that we're not calling
	 * `wp_reset_postdata()` because `$post` may not have been the current post
	 * from the global query.
	 *
	 * @access protected
	 */
	protected function restore_post() {
		global $post;

		$post = $this->original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		\setup_postdata( $post );
	}

	/**
	 * Get the string contents of the view.
	 *
	 * @return string
	 */
	public function render(): string {
		ob_start();

		$this->set_parent_from_current();

		// Setup the post object if needed.
		if ( isset( $this->post ) ) {
			$this->preserve_post();
			$this->setup_post_object();
		}

		$this->factory->push( $this );

		if ( 0 === validate_file( $this->slug ) && 0 === validate_file( $this->slug ) ) {
			$this->factory->get_container()['view.loader']->load( $this->slug, $this->name );
		}

		$this->factory->pop();

		if ( isset( $this->post ) ) {
			$this->restore_post();
		}

		return ob_get_clean();
	}

	/**
	 * Get the string contents of the view.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->render();
	}
}
