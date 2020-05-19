<?php
/**
 * Post_Query_Builder class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Query;

use Mantle\Framework\Support\Collection;

use function Mantle\Framework\Helpers\collect;

/**
 * Post Query Builder
 */
class Post_Query_Builder extends Builder {
	/**
	 * Query Variable Aliases
	 *
	 * @var array
	 */
	protected $query_aliases = [
		'id'          => 'p',
		'post_author' => 'author',
		'post_name'   => 'name',
	];

	/**
	 * Query Where In Aliases
	 *
	 * @var array
	 */
	protected $query_where_in_aliases = [
		'author'      => 'author__in',
		'id'          => 'post__in',
		'post_name'   => 'post_name__in',
		'post_parent' => 'post_parent__in',
		'tag'         => 'tag__in',
		'tag_slug'    => 'tag_slug__in',
	];

	/**
	 * Query Where Not In Aliases
	 *
	 * @var array
	 */
	protected $query_where_not_in_aliases = [
		'author'      => 'author__not_in',
		'id'          => 'post__not_in',
		'post_name'   => 'post_name__not_in',
		'post_parent' => 'post_parent__not_in',
		'tag'         => 'tag__not_in',
		'tag_slug'    => 'tag_slug__not_in',
	];

	/**
	 * Get the query arguments.
	 *
	 * @return array
	 */
	protected function get_query_args(): array {
		return array_merge(
			[
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'meta_query'          => $this->meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'tax_query'           => $this->tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'order'               => $this->order,
				'orderby'             => $this->order_by,
				'post_type'           => $this->model::get_object_name(),
				'posts_per_page'      => $this->limit,
				'suppress_filters'    => false,
			],
			$this->wheres,
		);
	}

	/**
	 * Execute the query.
	 *
	 * @return Collection
	 */
	public function get(): Collection {
		$post_ids = \get_posts( $this->get_query_args() );

		if ( empty( $post_ids ) ) {
			return collect();
		}

		$models = array_map( [ $this->model, 'find' ], $post_ids );
		return collect( array_filter( $models ) );
	}
}
