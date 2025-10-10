<?php
/**
 * Has_Many class file.
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
 * Has Many Relationship
 *
 * @template TParent of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
 * @template TModel of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
 *
 * @extends Has_One_Or_Many<TParent, TModel>
 */
class Has_Many extends Has_One_Or_Many {
	/**
	 * Get the results of the relationship.
	 *
	 * @return \Mantle\Support\Collection<int, TModel>|null
	 */
	public function get_results() {
		$this->add_constraints();

		return $this->query->get();
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param Collection<int, TModel> $models Parent models.
	 * @param Collection<int, TModel> $results Eagerly loaded results to match.
	 * @return Collection<int, TModel>
	 */
	public function match( Collection $models, Collection $results ): Collection {
		$dictionary = $this->build_dictionary( $results, $models ); // @phpstan-ignore-line argument.type

		return $models->each(
			function ( $model ) use ( $dictionary ): void {
				$key = $model[ $this->local_key ];
				$model->set_relation( $this->relationship, $dictionary[ $key ] ?? null ); // @phpstan-ignore-line
			}
		);
	}
}
