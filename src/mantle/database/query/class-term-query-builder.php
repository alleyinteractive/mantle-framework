<?php
/**
 * Term_Query_Builder class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Query;

use Mantle\Support\Collection;
use function Mantle\Support\Helpers\collect;

/**
 * Term Query Builder
 */
class Term_Query_Builder extends Builder {
	use Queries_Relationships;

	/**
	 * Query Variable Aliases
	 *
	 * @var array
	 */
	protected $query_aliases = [
		'id'      => 'include',
		'term_id' => 'include',
	];

	/**
	 * Query Where In Aliases
	 *
	 * @var array
	 */
	protected $query_where_in_aliases = [
		'term_id' => 'include',
		'name'    => 'name',
		'slug'    => 'slug',
	];

	/**
	 * Query Where Not In Aliases
	 *
	 * @var array
	 */
	protected $query_where_not_in_aliases = [
		'name'    => 'name',
		'slug'    => 'slug',
		'term_id' => 'exclude',
	];

	/**
	 * Order of the query.
	 *
	 * @var string
	 */
	protected $order = 'ASC';

	/**
	 * Query by of the query.
	 *
	 * @var string
	 */
	protected $order_by = 'name';

	/**
	 * Get the query arguments.
	 *
	 * @return array
	 */
	protected function get_query_args(): array {
		if ( is_array( $this->model ) ) {
			$taxonomies = [];

			foreach ( $this->model as $model ) {
				$taxonomies[] = $model::get_object_name();
			}
		} else {
			$taxonomies = $this->model::get_object_name();
		}

		// Limit is handled differently for term queries.
		if ( -1 === $this->limit ) {
			$this->limit = '';
		}

		return array_merge(
			[
				'fields'     => 'ids',
				'hide_empty' => false,
				'meta_query' => $this->meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'number'     => $this->limit,
				'order'      => $this->order,
				'orderby'    => $this->order_by,
				'taxonomy'   => $taxonomies,
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
		$term_ids = \get_terms( $this->get_query_args() );

		if ( empty( $term_ids ) ) {
			return collect();
		}

		$models = array_map( [ $this->model, 'find' ], $term_ids );
		$models = collect( array_filter( $models ) );

		return $this->eager_load_relations( $models );
	}
}
