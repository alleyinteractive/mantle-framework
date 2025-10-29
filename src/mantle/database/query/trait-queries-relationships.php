<?php
/**
 * Queries_Relationships trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Query;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Model_Meta;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Database\Query\Collection;

/**
 * Support querying against model relationships.
 *
 * @template TModel of Core_Object&Model_Meta&Updatable&Model
 * @mixin \Mantle\Database\Query\Post_Query_Builder<TModel>
 */
trait Queries_Relationships {
	/**
	 * Query the existence or a specific value in a model's relationship.
	 *
	 * @param string $relation Model relationship.
	 * @param string $compare Value to compare against, optional.
	 *
	 * @throws Query_Exception Thrown on invalid arguments.
	 *
	 * @return Builder<TModel>
	 */
	public function has( string $relation, ?string $compare = null ): Builder {
		$relation = $this->get_relation( $relation );
		if ( ! $relation ) {
			throw new Query_Exception( 'Unknown relation on model: ' . $relation );
		}

		if ( ! method_exists( $relation, 'get_relation_query' ) ) {
			throw new Query_Exception( 'Relationship does not support querying against it: ' . $relation::class );
		}

		return $relation->get_relation_query( $this, $compare );
	}

	/**
	 * Query the non-existence of a model's relationship.
	 *
	 * @param string $relation Model relationship.
	 * @param string $compare Value to compare against, optional.
	 * @return Builder<TModel>
	 *
	 * @throws Query_Exception Thrown on invalid arguments.
	 */
	public function doesnt_have( string $relation, ?string $compare = null ) {
		$relation = $this->get_relation( $relation );
		if ( ! $relation ) {
			throw new Query_Exception( 'Unknown relation on model: ' . $relation );
		}

		if ( ! method_exists( $relation, 'get_relation_query' ) ) {
			throw new Query_Exception( 'Relationship does not support querying against it: ' . $relation::class );
		}

		$comparison = $compare ? '!=' : 'NOT EXISTS';
		return $relation->get_relation_query( $this, $compare, $comparison );
	}

	/**
	 * Get the model relationship instance.
	 *
	 * @param string $relation Relationship name.
	 */
	protected function get_relation( $relation ): ?Relation {
		$model = $this->get_model();

		if ( is_array( $model ) ) {
			return null;
		}

		return ( new $model() )->{ $relation }();
	}

	/**
	 * Eager load relations for a set of models.
	 *
	 * @param Collection<int, TModel> $models Models to load for.
	 * @return Collection<int, TModel>
	 */
	protected function eager_load_relations( Collection $models ): Collection {
		foreach ( $this->eager_load as $name ) {
			$models = $this->eager_load_relation( $models, $name );
		}

		return $models;
	}

	/**
	 * Eager load a relation on a set of models.
	 *
	 * @param Collection<int, TModel> $models Model instances.
	 * @param string                  $name Relation name to eager load.
	 * @return Collection<int, TModel>
	 */
	protected function eager_load_relation( Collection $models, string $name ): Collection {
		$relation = $this->get_relation( $name );

		$results = Relation::no_constraints(
			function () use ( $models, $relation ) {
				if ( ! $relation ) {
					return Collection::from( [] );
				}

				// Add the eager constraints from the relation to the query.
				$relation->add_eager_constraints( $models ); // @phpstan-ignore-line

				return $relation->get_eager();
			}
		);

		return $relation->match( $models, $results ); // @phpstan-ignore-line
	}
}
