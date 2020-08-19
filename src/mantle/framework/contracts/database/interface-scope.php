<?php
/**
 * Scope interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Database;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Query\Builder;

/**
 * Query Scope Contract
 */
interface Scope {
	/**
	 * Apply the scope to a given query builder.
	 *
	 * @param Builder $builder Query Builder instance.
	 * @param Model   $model Model object.
	 */
	public function apply( Builder $builder, Model $model );
}
