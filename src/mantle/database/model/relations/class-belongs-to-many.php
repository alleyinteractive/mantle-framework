<?php
/**
 * Belongs_To_Many class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Model_Meta;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Model\Model;
use Mantle\Support\Collection;
use RuntimeException;

use function Mantle\Support\Helpers\collect;

/**
 * Creates a 'Belongs To Many' relationship.
 *
 * @template TParent of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
 * @template TModel of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
 *
 * @extends Belongs_To<TParent, TModel>
 */
class Belongs_To_Many extends Belongs_To {
	/**
	 * Retrieve the results of the query.
	 *
	 * @return Collection<int, TModel>|null
	 */
	public function get_results() {
		$this->add_constraints();

		return $this->query->get();
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param Collection<int, TParent> $models Parent models.
	 * @param Collection<int, TModel>  $results Eagerly loaded results to match.
	 */
	public function match( Collection $models, Collection $results ): Collection {
		$dictionary = $this->build_dictionary( $results, $models ); // @phpstan-ignore-line argument.type

		return $models->each(
			function ( $model ) use ( $dictionary ): void {
				$key = $model->{$this->foreign_key};

				if ( ! method_exists( $model, 'set_relation' ) ) {
					throw new RuntimeException( 'Model does not implement set_relation method.' );
				}

				$model->set_relation( $this->relationship, $dictionary[ $key ] ?? null );
			}
		);
	}

	/**
	 * Build a model dictionary keyed by the relation's foreign key.
	 *
	 * @throws RuntimeException If the local key is not defined.
	 *
	 * @param Collection<int, TParent> $results Collection of results.
	 * @param Collection<int, TModel>  $models Eagerly loaded results to match.
	 * @return array<string, array<int, TParent>>
	 */
	protected function build_dictionary( Collection $results, Collection $models ): array {
		$results    = $results->key_by( $this->foreign_key );
		$dictionary = collect();

		if ( ! $this->local_key ) {
			throw new RuntimeException( 'Local key is not defined for Belongs To Many relationship.' );
		}

		foreach ( $models as $model ) {
			$dictionary[ $model->{$this->foreign_key} ] = (array) $model->get_meta( $this->local_key, false );
		}

		return $dictionary
			->map(
				fn ( $child_ids ) => $results->only( $child_ids )->values()->all()
			)
			->filter()
			->all();
	}
}
