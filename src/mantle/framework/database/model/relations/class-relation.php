<?php
/**
 * Relation class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Query\Builder;
use Mantle\Framework\Support\Forward_Calls;

/**
 * Relation base class.
 */
abstract class Relation {
	use Forward_Calls;

	/**
	 * Query Builder instance.
	 *
	 * @var Builder
	 */
	protected $query;

	/**
	 * Create a new relation instance.
	 *
	 * @param Builder $query Query builder instance.
	 * @param Model $parent Model instance.
	 */
	public function __construct( Builder $query, Model $parent ) {
		$this->query = $query;
		$this->parent = $parent;
		$this->related = $query->get_model();

		$this->add_constraints();
	}

	/**
	 * Set the query constraints to apply to the query.
	 */
	abstract public function add_constraints();

	/**
	 * Handle dynamic method calls to the relationship.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call( string $method, array $parameters ) {
		$result = $this->forward_call_to( $this->query, $method, $parameters );

		if ( $this->query === $result ) {
			return $this;
		}

		return $result;
	}
}
