<?php
/**
 * Paginator class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Pagination;

use ArrayAccess;
use Countable;
use JsonSerializable;
use Mantle\Framework\Contracts\Support\Arrayable;
use Mantle\Framework\Contracts\Support\Jsonable;
use Mantle\Framework\Database\Query\Builder;
use Mantle\Framework\Http\Request;
use Mantle\Framework\Support\Collection;
use Mantle\Framework\Support\Str;

/**
 * Paginator for query results.
 */
class Paginator implements Arrayable, ArrayAccess, Countable, Jsonable, JsonSerializable {
	/**
	 * Current page number
	 *
	 * @var int
	 */
	protected $current_page;

	/**
	 * Query builder instance.
	 *
	 * @var Builder
	 */
	protected $builder;

	/**
	 * Items being paginate.
	 *
	 * @var \Mantle\Framework\Support\Collection
	 */
	protected $items;

	/**
	 * Number of items per page.
	 *
	 * @var int
	 */
	protected $per_page;

	/**
	 * Path for URLs.
	 *
	 * @var string
	 */
	protected $path = '/';

	/**
	 * Flag if query strings should be used.
	 *
	 * @var bool
	 */
	protected $use_query_string = true;

	/**
	 * Set of query string values to use on every URL.
	 *
	 * @var array
	 */
	protected $query = [];

	/**
	 * The current path resolver callback.
	 *
	 * @var \Closure
	 */
	protected static $current_path_resolver;

	/**
	 * The current page resolver callback.
	 *
	 * @var \Closure
	 */
	protected static $current_page_resolver;

	/**
	 * The URL generator callback.
	 *
	 * @var \Closure
	 */
	protected static $url_generator;

	/**
	 * Constructor.
	 *
	 * @param Builder $builder Query builder instance.
	 * @param integer $per_page Items per-page.
	 * @param integer $current_page Current page to set.
	 */
	public function __construct( Builder $builder, int $per_page = 20, int $current_page = null ) {
		$this->builder  = $builder;
		$this->per_page = $per_page;

		$this->builder->take( $per_page );

		$this->set_current_page( $current_page );
		$this->set_items();
	}

	/**
	 * Set the path to use for the request.
	 *
	 * @param string $path Path to use.
	 * @return static
	 */
	public function path( string $path = null ) {
		if ( $path ) {
			$this->path = $path;
			return $this;
		}

		if ( isset( static::$current_path_resolver ) ) {
			$this->path = (string) call_user_func( static::$current_path_resolver, $this );
			return $this;
		}

		$request = app( Request::class );
	}

	/**
	 * Retrieve the path to use for the paginator.
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Flag if query strings should be used for the pagination URLs.
	 *
	 * @return static
	 */
	public function use_query_string() {
		$this->use_query_string = true;
		return $this;
	}

	/**
	 * Flag if query strings shouldn't be used for the pagination URLs.
	 *
	 * @return static
	 */
	public function use_path() {
		$this->use_query_string = false;
		return $this;
	}

	/**
	 * Set the current page for the paginator.
	 *
	 * @param int $current_page Current page to set, optional.
	 * @return static
	 */
	public function set_current_page( int $current_page = null ) {
		if ( $current_page && $current_page > 0 ) {
			$this->current_page = $current_page;
		} else {
			$this->current_page = static::resolve_current_page();
		}

		$this->builder->page( $this->current_page );
		return $this;
	}

	/**
	 * Retrieve the current page.
	 *
	 * @return int
	 */
	public function current_page(): int {
		return $this->current_page;
	}

	/**
	 * Set the items for the paginator.
	 *
	 * @return static
	 */
	protected function set_items() {
		$this->items = $this->builder->get();
		return $this;
	}

	/**
	 * Retrieve the items for the paginator.
	 *
	 * @return Collection
	 */
	public function items(): Collection {
		return $this->items;
	}

	/**
	 * Count the number of items.
	 *
	 * @return int
	 */
	public function count(): int {
		return $this->items->count();
	}

	/**
	 * Append query string value to the paginator.
	 *
	 * @param string|array $key Query string key or array of key value pairs.
	 * @param mixed        $value Query string value.
	 * @return static
	 */
	public function append( $key, $value = null ) {
		if ( is_array( $key ) && null === $value ) {
			foreach ( $this->append as $k => $v ) {
				$this->append( $k, $v );
			}

			return $this;
		}

		$this->query[ $key ] = $value;
		return $this;
	}

	/**
	 * Append the current query string parameters.
	 *
	 * @return static
	 */
	public function with_query_string() {
		$this->append( $this->request()->query() );
		return $this;
	}

	/**
	 * Retrieve the query strings for the paginator.
	 *
	 * @return array
	 */
	public function query(): array {
		return $this->query;
	}

	/**
	 * Get the current request object.
	 *
	 * @return Request
	 */
	protected static function request(): Request {
		return app( Request::class );
	}

	/**
	 * Resolve the current page.
	 *
	 * @return int
	 */
	protected static function resolve_current_page(): int {
		if ( static::$current_page_resolver ) {
			return call_user_func( static::$current_page_resolver );
		}

		$request = static::request();
		$query   = $request->query( 'page' );
		if ( $query ) {
			return (int) $query;
		}

		$path = $request->path();

		if ( ! Str::is( 'page/*', $path ) ) {
			return 1;
		}

		preg_match_all( '/page\/(\d*)\/?/', $path, $matches );
		return (int) ( $matches[1][0] ?? 1 );
	}

	/**
	 * Set the current page resolver.
	 *
	 * @param callable $callback Callback for the resolver.
	 * @return void
	 */
	public static function current_page_resolver( callable $callback ): void {
		static::$current_page_resolver = $callback;
	}

	/**
	 * Set the current path resolver.
	 *
	 * @param callable $callback Callback for the resolver.
	 * @return void
	 */
	public static function current_path_resolver( callable $callback ): void {
		static::$current_path_resolver = $callback;
	}

	/**
	 * Generate a URL for a given page.
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	public function url( int $page ): string {
		if ( isset( static::$url_generator ) ) {
			return call_user_func( static::$url_generator, $page, $this );
		}

		if ( $this->use_query_string ) {
			if ( $page < 1 ) {
				return add_query_arg( $this->query, $this->path );
			}

			return add_query_arg(
				[
					'page' => $page,
				] + $this->query,
				$this->path
			);
		}

		if ( $page < 1 ) {
			return add_query_arg( $this->query, $this->path );
		}

		return add_query_arg( $this->query, trailingslashit( $this->path ) . 'page/' . $page . '/' );
	}

	/**
	 * Retrieve the URL for the next page.
	 *
	 * @return string|null
	 */
	public function next_url(): ?string {
		return $this->url( $this->current_page() + 1 );
	}

	/**
	 * Retrieve the URL for the previous page.
	 *
	 * @return string|null
	 */
	public function previous_url(): ?string {
		if ( $this->current_page() > 1 ) {
			return $this->url( $this->current_page() - 1 );
		}

		return null;
	}

	/**
	 * Convert the paginator to an array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'current_page'   => $this->current_page(),
			'data'           => $this->items->to_array(),
			'first_page_url' => $this->url( 1 ),
			'next_url'       => $this->next_url(),
			'path'           => $this->path,
			'previous_url'   => $this->previous_url(),
		];
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param int $options Options for json_encode().
	 * @return string
	 */
	public function to_json( $options = 0 ) {
		return wp_json_encode( $this->jsonSerialize(), $options );
	}

	/**
	 * Check if an item exists at an offset.
	 *
	 * @param mixed $offset Array offset.
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->items[ $offset ] );
	}

	/**
	 * Retrieve an item by its offset.
	 *
	 * @param mixed $offset Offset to get.
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->items[ $offset ];
	}

	/**
	 * Set an item by its offset.
	 *
	 * @param mixed $offset Offset to get.
	 * @param mixed $value  Value to set.
	 * @return void
	 */
	public function offsetSet( $offset, $value ): void {
		$this->items[ $offset ] = $value;
	}

	/**
	 * Delete by an offset.
	 *
	 * @param mixed $offset Offset to delete.
	 * @return void
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->items[ $offset ] );
	}
}
