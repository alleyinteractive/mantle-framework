<?php
/**
 * Has_One class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Mantle\Support\Collection;

/**
 * Has One Relationship
 *
 * @template TParent of \Mantle\Database\Model\Model = \Mantle\Database\Model\Model
 * @template TModel of \Mantle\Database\Model\Model = \Mantle\Database\Model\Model
 *
 * @extends Has_One_Or_Many<TParent, TModel>
 */
class Has_One extends Has_One_Or_Many {
	/**
	 * Get the results of the relationship.
	 *
	 * @return \Mantle\Database\Model\Model|null
	 * @phpstan-return TModel|null
	 */
	public function get_results() {
		$this->add_constraints();

		return $this->query->first();
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param Collection<int, TParent> $models Parent models.
	 * @param Collection<int, TModel>  $results Eagerly loaded results to match.
	 * @return Collection<int, TParent>
	 */
	public function match( Collection $models, Collection $results ): Collection {
		$dictionary = $this->build_dictionary( $results, $models );

		return $models->each(
			function ( $model ) use ( $dictionary ): void {
				$key = $model[ $this->local_key ];
				$model->set_relation( $this->relationship, $dictionary[ $key ][0] ?? null );
			}
		);
	}
}
