<?php
/**
 * Post_Query_Builder class file.
 *
 * @package Mantle
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Database\Query;

use Mantle\Database\Model\Term;
use Mantle\Support\Helpers;
use Mantle\Support\Collection;
use WP_Term;

use function Mantle\Support\Helpers\collect;

/**
 * Post Query Builder
 */
class Post_Query_Builder extends Builder {
	use Queries_Relationships;

	/**
	 * Query Variable Aliases
	 *
	 * @var array
	 */
	protected $query_aliases = [
		'id'          => 'p',
		'post_author' => 'author',
		'post_name'   => 'name',
		'slug'        => 'name',
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
	 * Tax Query.
	 *
	 * @var array
	 */
	protected $tax_query = [];

	/**
	 * Get the query arguments.
	 *
	 * @return array
	 */
	public function get_query_args(): array {
		$this->apply_scopes();

		if ( is_array( $this->model ) ) {
			$post_type = [];

			foreach ( $this->model as $model ) {
				$post_type[] = $model::get_object_name();
			}
		} else {
			$post_type = $this->model::get_object_name();
		}

		return array_merge(
			[
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'meta_query'          => $this->meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'order'               => $this->order,
				'orderby'             => $this->order_by,
				'paged'               => $this->page,
				'post_type'           => $post_type,
				'posts_per_page'      => $this->limit,
				'suppress_filters'    => false,
				'tax_query'           => $this->tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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
		$query            = new \WP_Query( $this->get_query_args() );
		$this->found_rows = $query->found_posts;
		$post_ids         = $query->posts;

		if ( empty( $post_ids ) ) {
			return collect();
		}

		$models = $this->get_models( $post_ids );

		// Return the models if there are no models or if multiple model instances
		// are used. Eager loading does not currently support multiple models.
		if ( $models->is_empty() || is_array( $this->model ) || empty( $this->eager_load ) ) {
			return $models;
		}

		return $this->eager_load_relations( $models );
	}

	/**
	 * Retrieve hydrated models for the post IDs.
	 *
	 * @param int[] $post_ids Post IDs.
	 * @return Collection
	 */
	protected function get_models( array $post_ids ): Collection {
		if ( is_array( $this->model ) ) {
			$model_object_types = $this->get_model_object_names();
			return collect( $post_ids )
				->map(
					function ( $post_id ) use ( $model_object_types ) {
						$post_type = \get_post_type( $post_id );

						if ( empty( $post_type ) ) {
							return null;
						}

						return $model_object_types[ $post_type ]::find( $post_id );
					}
				)
				->filter();
		} else {
			return collect( $post_ids )
				->map( [ $this->model, 'find' ] )
				->filter();
		}
	}

	/**
	 * Include a taxonomy query.
	 *
	 * @param array|string $term Term ID/array of IDs.
	 * @param string       $taxonomy Taxonomy name.
	 * @param string       $operator Operator to use, defaults to 'IN'.
	 * @param string       $field Field to use for the query, defaults to term ID.
	 *
	 * @throws Query_Exception Unknown term to query against.
	 */
	public function whereTerm( $term, $taxonomy = null, string $operator = 'IN', string $field = 'term_id' ) {
		if ( $term instanceof Term ) {
			$taxonomy = $term->taxonomy();
			$term     = $term->id();
		}

		if ( $term instanceof \WP_Term ) {
			$taxonomy = $term->taxonomy;
			$term     = $term->term_id;
		}

		// Get the taxonomy if it wasn't passed.
		if ( empty( $taxonomy ) && ! is_array( $term ) ) {
			// Attempt to resolve the term from the slug.
			if ( 'slug' === $field ) {
				$object = get_term_by( 'slug', $term, $taxonomy );

				if ( ! ( $term instanceof WP_Term ) ) {
					throw new Query_Exception( 'Unknown term to query against with slug (must pass taxonomy): ' . $term );
				}
			} else {
				$object = Helpers\get_term_object( $term );
			}

			if ( empty( $object ) ) {
				throw new Query_Exception( 'Unknown term: ' . $term );
			}

			$taxonomy = $object->taxonomy;
			$term     = $object->term_id;
		}

		$this->tax_query[] = [
			'field'            => $field,
			'include_children' => true,
			'operator'         => $operator,
			'taxonomy'         => $taxonomy,
			'terms'            => $term,
		];

		return $this;
	}

	/**
	 * Include a taxonomy query with the relation set to 'AND'.
	 *
	 * @param array|string $term Term ID/array of IDs.
	 * @param string       $taxonomy Taxonomy name.
	 * @param string       $operator Operator to use, defaults to 'IN'.
	 */
	public function andWhereTerm( ...$args ) {
		$this->tax_query['relation'] = 'AND';
		return $this->whereTerm( ...$args );
	}

	/**
	 * Include a taxonomy query with the relation set to 'OR'.
	 *
	 * @param array|string $term Term ID/array of IDs.
	 * @param string       $taxonomy Taxonomy name.
	 * @param string       $operator Operator to use, defaults to 'IN'.
	 */
	public function orWhereTerm( ...$args ) {
		$this->tax_query['relation'] = 'OR';
		return $this->whereTerm( ...$args );
	}
}
