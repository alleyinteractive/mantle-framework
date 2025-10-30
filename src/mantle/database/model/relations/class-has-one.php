<?php
/**
 * Has_One class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Model_Meta;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Model\Model;
use Mantle\Support\Collection;

/**
 * Has One Relationship
 *
 * @template TParent of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
 * @template TModel of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
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
				$model->set_relation( $this->relationship, $dictionary[ $key ][0] ?? null ); // @phpstan-ignore-line method.notFound
			}
		);
	}
}
