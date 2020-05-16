<?php
/**
 * Post_Query_Builder class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Query;

/**
 * Post Query Builder
 */
class Post_Query_Builder extends Builder {
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
	 * @return array
	 */
	public function get(): array {
		$post_ids = \get_posts( $this->get_query_args() );

		if ( empty( $post_ids ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map( [ $this->model, 'find' ], $post_ids )
			)
		);
	}

	public function whereIn( string $attribute, array $values ) {
		if ( $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		switch ( $attribute ) {
			case 'ID':
				$query_attribute = 'post__in';
				break;

			case 'post_name':
				$query_attribute = 'post_name__in';
				break;

			case 'post_parent':
				$query_attribute = 'post_parent__in';
				break;

			default:
				$query_attribute = false;
		}

		if ( empty( $query_attribute ) ) {
			throw new Query_Exception( 'Unknown attribute for "whereIn": ' . $attribute );
		}

		return $this->where( $query_attribute, (array) $values );
	}

	public function whereNotIn( string $attribute, array $values ) {
		if ( $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		switch ( $attribute ) {
			case 'ID':
				$query_attribute = 'post__not_in';
				break;

			case 'post_name':
				$query_attribute = 'post_name__not_in';
				break;

			case 'post_parent':
				$query_attribute = 'post_parent__not_in';
				break;

			default:
				$query_attribute = false;
		}

		if ( empty( $query_attribute ) ) {
			throw new Query_Exception( 'Unknown attribute for "whereNotIn": ' . $attribute );
		}

		return $this->where( $query_attribute, $values );
	}
}
