<?php
/**
 * Queries_Relationships trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Query;

use Mantle\Database\Model\Relations\Relation;

/**
 * Support querying against model relationships.
 */
trait Queries_Relationships {
	/**
	 * Query the existence or a specific value in a model's relationship.
	 *
	 * @param string $relation Model relationship.
	 * @param string $compare Value to compare against, optional.
	 * @return Builder
	 *
	 * @throws Query_Exception Thrown on invalid arguments.
	 */
	public function has( string $relation, string $compare = null ): Builder {
		$relation = $this->get_relation( $relation );
		if ( ! $relation ) {
			throw new Query_Exception( 'Unknown relation on model: ' . $relation );
		}

		if ( ! method_exists( $relation, 'get_relation_query' ) ) {
			throw new Query_Exception( 'Relationship does not support querying against it: ' . get_class( $relation ) );
		}

		return $relation->get_relation_query( $this, $compare );
	}

	/**
	 * Query the non-existence of a model's relationship.
	 *
	 * @param string $relation Model relationship.
	 * @param string $compare Value to compare against, optional.
	 * @return Builder
	 *
	 * @throws Query_Exception Thrown on invalid arguments.
	 */
	public function doesnt_have( string $relation, string $compare = null ) {
		$relation = $this->get_relation( $relation );
		if ( ! $relation ) {
			throw new Query_Exception( 'Unknown relation on model: ' . $relation );
		}

		if ( ! method_exists( $relation, 'get_relation_query' ) ) {
			throw new Query_Exception( 'Relationship does not support querying against it: ' . get_class( $relation ) );
		}

		$comparison = $compare ? '!=' : 'NOT EXISTS';
		return $relation->get_relation_query( $this, $compare, $comparison );
	}

	/**
	 * Get the model relationship instance.
	 *
	 * @param string $relation Relationship name.
	 * @return Relation|null
	 */
	protected function get_relation( $relation ): ?Relation {
		$model = $this->get_model();

		if ( is_array( $model ) ) {
			return null;
		}

		return ( new $model() )->{ $relation }();
	}
}
