<?php
/**
 * View class file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

namespace Mantle\Http\View;

use Mantle\Contracts\Http\View\Factory as Factory_Contract;
use Mantle\Contracts\View\Engine;
use Mantle\Database\Model\Post;
use Mantle\Support\Arr;
use WP_Post;

/**
 * View Class
 */
class View implements \Stringable {
	/**
	 * Post object to set for the post.
	 */
	protected Post|\WP_Post|int|null $post = null;

	/**
	 * The original post to restore after rendering the view.
	 */
	protected ?WP_Post $original_post = null;

	/**
	 * Cache key to use.
	 */
	protected ?string $cache_key = null;

	/**
	 * Cache TTL for the view.
	 */
	protected ?int $cache_ttl = null;

	/**
	 * Constructor.
	 *
	 * @param Factory_Contract     $factory View Factory.
	 * @param Engine               $engine View Engine.
	 * @param string               $path View path.
	 * @param array<string, mixed> $data Variables for the view, optional.
	 */
	public function __construct(
		protected Factory_Contract $factory,
		protected Engine $engine,
		protected string $path,
		protected array $data = [],
	) {
	}

	/**
	 * Get the view path.
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Set the post for the view.
	 *
	 * Allows the global WordPress post object to be adjusted when rendering the view.
	 *
	 * @param Post|\WP_Post|int $post Post object.
	 */
	public function set_post( WP_Post|Post|int $post ): static {
		$this->post = $post;
		return $this;
	}

	/**
	 * Add a piece of data to the view.
	 *
	 * @param string|array<string, mixed> $key Key to set.
	 * @param mixed                       $value Value to set.
	 */
	public function with( string|array $key, mixed $value = null ): static {
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
	 * @return array<string, mixed>
	 */
	public function get_variables(): array {
		return $this->data;
	}

	/**
	 * Get a specific variable for the view.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default Default value, optional.
	 */
	public function get_variable( string $key, mixed $default = null ): mixed {
		return Arr::get( $this->data, $key, $default );
	}

	/**
	 * Set the cache TTL for the view.
	 *
	 * @param int|bool $ttl Cache TTL or false to disable. Defaults to 15 minutes.
	 * @param string   $key Cache key to use, optional.
	 */
	public function cache( int|bool $ttl = 900, ?string $key = null ): static {
		$ttl = match ( $ttl ) {
			false => null,
			true => 0, // Indefinite.
			default => $ttl,
		};

		$this->cache_ttl = $ttl;

		$this->cache_key = $key;

		return $this;
	}

	/**
	 * Retrieve the cache key to use for the view.
	 */
	public function get_cache_key(): string {
		if ( $this->cache_key ) {
			return $this->cache_key;
		}

		$filtered_data = array_map(
			function ( $value, int|string $key ) {
				// Internal class references do not serialize well.
				if ( '__env' === $key ) {
					return 'app';
				}

				if ( is_object( $value ) ) {
					return spl_object_hash( $value );
				}

				return $value;
			},
			$this->data,
			array_keys( $this->data )
		);

		return 'partial_' . md5( $this->path . serialize( $filtered_data ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
	}

	/**
	 * Set the global post object for the view.
	 */
	protected function setup_post_object(): void {
		global $post;

		if ( $this->post === null ) {
			return;
		}

		$this->preserve_post();

		$post = $this->post instanceof Post ? \get_post( $this->post->id() ) : \get_post( $this->post );

		if ( $post ) {
			\setup_postdata( $post );
		}
	}

	/**
	 * Backup the current global `$post`.
	 */
	protected function preserve_post(): void {
		$this->original_post = $GLOBALS['post'] ?? null;
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
	protected function restore_post(): void {
		global $post;

		$post = $this->original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( $post instanceof \WP_Post ) {
			\setup_postdata( $post );
		}
	}

	/**
	 * Get the string contents of the view.
	 */
	public function render(): string {
		// Check the cache for the view.
		if ( $this->cache_ttl !== null ) {
			$cache_key = $this->get_cache_key();
			$contents  = \get_transient( $cache_key );

			if ( false !== $contents ) {
				return (string) $contents;
			}
		}

		// Setup the post object if needed.
		if ( $this->post !== null ) {
			$this->setup_post_object();
		}

		$this->factory->push( $this );

		// Invoke the engine to render the view.
		$contents = $this->engine->get( $this->path, $this->data );

		$this->factory->pop();

		if ( $this->post !== null ) {
			$this->restore_post();
		}

		if ( $this->cache_ttl !== null && isset( $cache_key ) ) {
			\set_transient( $cache_key, $contents, $this->cache_ttl );
		}

		return $contents;
	}

	/**
	 * Get the string contents of the view.
	 */
	public function __toString(): string {
		return $this->render();
	}
}
