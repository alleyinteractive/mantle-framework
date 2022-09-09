<?php
/**
 * Builder class file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Database\Query;

use Closure;
use Mantle\Container\Container;
use Mantle\Contracts\Database\Scope;
use Mantle\Contracts\Paginator\Paginator as PaginatorContract;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Model_Not_Found_Exception;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Database\Pagination\Length_Aware_Paginator;
use Mantle\Database\Pagination\Paginator;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use Mantle\Support\Traits\Conditionable;

use function Mantle\Support\Helpers\collect;

/**
 * Builder Query Builder
 */
abstract class Builder {
	use Conditionable;

	/**
	 * Model to build on.
	 *
	 * @var string[]|string
	 */
	protected $model;

	/**
	 * Result limit per-page.
	 *
	 * @var int
	 */
	protected $limit = 100;

	/**
	 * Result offset.
	 *
	 * @var int
	 */
	protected $offset = 0;

	/**
	 * Result page.
	 *
	 * @var int
	 */
	protected $page = 1;

	/**
	 * Where arguments for the query.
	 *
	 * @var array
	 */
	protected $wheres = [];

	/**
	 * Order of the query.
	 *
	 * @var string
	 */
	protected $order = 'DESC';

	/**
	 * Query by of the query.
	 *
	 * @var string
	 */
	protected $order_by = 'date';

	/**
	 * Meta Query.
	 *
	 * @var array
	 */
	protected $meta_query = [];

	/**
	 * Query Variable Aliases
	 *
	 * @var array
	 */
	protected $query_aliases = [];

	/**
	 * Query Where In Aliases
	 *
	 * @var array
	 */
	protected $query_where_in_aliases = [];

	/**
	 * Query Where Not In Aliases
	 *
	 * @var array
	 */
	protected $query_where_not_in_aliases = [];

	/**
	 * Applied global scopes.
	 *
	 * @var array
	 */
	protected $scopes = [];

	/**
	 * Storage of the found rows for a query.
	 *
	 * @var int
	 */
	protected $found_rows = 0;

	/**
	 * Relationships to eager load.
	 *
	 * @var string[]
	 */
	protected $eager_load = [];

	/**
	 * Constructor.
	 *
	 * @param array|string $model Model or array of model class names.
	 */
	public function __construct( $model ) {
		$this->model = $model;
	}

	/**
	 * Get the query results.
	 *
	 * @return Collection
	 */
	abstract public function get(): Collection;

	/**
	 * Get a model instance for the builder.
	 *
	 * @return string|string[]
	 */
	public function get_model() {
		return $this->model;
	}

	/**
	 * Get the model instance for the builder.
	 *
	 * @return Model
	 *
	 * @throws Query_Exception Thrown when trying to use with multiple models.
	 */
	protected function get_model_instance(): Model {
		if ( is_array( $this->model ) ) {
			throw new Query_Exception( 'Unable to get model instance for multiple models.' );
		}

		return new $this->model();
	}

	/**
	 * Retrieve the found rows for a query.
	 *
	 * @return int
	 */
	public function get_found_rows(): int {
		return $this->found_rows;
	}

	/**
	 * Query an attribute against a list.
	 *
	 * @param string $attribute Attribute to query against.
	 * @param array  $values List of values.
	 * @return static
	 *
	 * @throws Query_Exception Thrown on an unmapped attribute being used.
	 */
	public function whereIn( string $attribute, array $values ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_in_aliases[ strtolower( $attribute ) ];
		} else {
			throw new Query_Exception( 'Unknown where in alias: ' . $attribute );
		}

		return $this->where( $attribute, (array) $values );
	}

	/**
	 * Query an where an attribute is not in a list.
	 *
	 * @param string $attribute Attribute to query against.
	 * @param array  $values List of values.
	 * @return static
	 *
	 * @throws Query_Exception Thrown on an unmapped attribute being used.
	 */
	public function whereNotIn( string $attribute, array $values ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_not_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_not_in_aliases[ strtolower( $attribute ) ];
		} else {
			throw new Query_Exception( 'Unknown where not in alias: ' . $attribute );
		}

		return $this->where( $attribute, $values );
	}

	/**
	 * Create a query builder for a model.
	 *
	 * @param array|string $model Model name or array of model names.
	 * @return static
	 */
	public static function create( $model ) {
		return new static( $model );
	}

	/**
	 * Add a where clause to the query.
	 *
	 * @param string|array $attribute Attribute to use or array of key => value
	 *                                attributes to set.
	 * @param mixed        $value Value to compare against.
	 * @return static
	 */
	public function where( $attribute, $value = '' ) {
		if ( is_array( $attribute ) && empty( $value ) ) {
			foreach ( $attribute as $key => $value ) {
				$this->where( $key, $value );
			}

			return $this;
		}

		if ( ! empty( $this->query_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_aliases[ strtolower( $attribute ) ];
		}

		$this->wheres[ $attribute ] = $value;
		return $this;
	}

	/**
	 * Support a dynamic where query.
	 *
	 * @param string $method Method name.
	 * @param array  $args Arguments.
	 * @return static
	 */
	public function dynamicWhere( $method, $args ) {
		$finder = substr( $method, 5 );

		$attribute = Str::snake( $finder );

		// Use the model's alias if one exist.
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		return $this->where( $attribute, ...$args );
	}

	/**
	 * Query by a meta field.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param string $compare Comparison method, defaults to '='.
	 * @return static
	 */
	public function whereMeta( $key, $value, string $compare = '=' ) {
		$meta_query = [
			'compare' => $compare,
			'key'     => $key,
			'value'   => $value,
		];

		// Remove the value from meta queries checking for existence.
		if ( empty( $value ) && ( 'EXISTS' === $compare || 'NOT EXISTS' === $compare ) ) {
			unset( $meta_query['value'] );
		}

		$this->meta_query[] = $meta_query;
		return $this;
	}

	/**
	 * Query by a meta field with the relation set to 'and'.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param string $compare Comparison method, defaults to '='.
	 * @return static
	 */
	public function andWhereMeta( ...$args ) {
		$this->meta_query['relation'] = 'AND';
		return $this->whereMeta( ...$args );
	}

	/**
	 * Query by a meta field with the relation set to 'or'.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param string $compare Comparison method, defaults to '='.
	 * @return static
	 */
	public function orWhereMeta( ...$args ) {
		$this->meta_query['relation'] = 'OR';
		return $this->whereMeta( ...$args );
	}

	/**
	 * Order the query by a field.
	 *
	 * @param string $attribute Attribute name.
	 * @param string $direction Order direction.
	 * @return static
	 */
	public function orderBy( $attribute, string $direction = 'asc' ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_aliases[ strtolower( $attribute ) ];
		}

		$this->order    = strtoupper( $direction );
		$this->order_by = $attribute;
		return $this;
	}

	/**
	 * Support a dynamic order by (orderByName(...)).
	 *
	 * @param string $method Method name. Attribute name.
	 * @param array $args Method arguments.
	 * @return static
	 */
	public function dynamicOrderBy( string $method, array $args ) {
		$attribute = Str::snake( substr( $method, 7 ) );

		$attribute = str_replace( '_in', '__in', $attribute );
		return $this->orderBy( $attribute, $args[0] ?? 'asc' );
	}

	/**
	 * Order by the value passed in `whereIn()`.
	 *
	 * @param string $attribute Attribute to use.
	 * @return static
	 *
	 * @throws Query_Exception Thrown on unknown alias.
	 */
	public function orderByWhereIn( string $attribute ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_in_aliases[ strtolower( $attribute ) ];
		} else {
			throw new Query_Exception( 'Unknown where in alias: ' . $attribute );
		}

		return $this->orderBy( $attribute );
	}

	/**
	 * Determine if the given model has a scope.
	 *
	 * @param string $scope Scope name.
	 * @return bool
	 */
	public function has_named_scope( string $scope ): bool {
		// Disable model scopes for multi-model queries.
		if ( is_array( $this->model ) ) {
			return false;
		}

		return $this->get_model_instance()->has_named_scope( $scope );
	}

	/**
	 * Apply the given scope on the current builder instance.
	 *
	 * @param callable $scope Scope callback.
	 * @param array    $parameters Scope parameters.
	 * @return mixed
	 */
	protected function call_scope( callable $scope, array $parameters = [] ) {
		array_unshift( $parameters, $this );

		return $scope( ...array_values( $parameters ) ) ?? $this;
	}

	/**
	 * Apply the given named scope on the current builder instance.
	 *
	 * @param string $scope Scope name.
	 * @param array  $parameters Scope parameters.
	 * @return mixed
	 */
	protected function call_named_scope( string $scope, array $parameters = [] ) {
		return $this->call_scope(
			function ( ...$parameters ) use ( $scope ) {
				return $this->get_model_instance()->call_named_scope( $scope, $parameters );
			},
			$parameters
		);
	}

	/**
	 * Apply the scopes to the Eloquent builder instance and return it.
	 *
	 * @return static
	 */
	protected function apply_scopes() {
		// Ignore query builders across multiple models.
		if ( is_array( $this->model ) || empty( $this->scopes ) ) {
			return $this;
		}

		foreach ( $this->scopes as $identifier => $scope ) {
			$this->call_scope(
				function( self $builder ) use ( $scope ) {
					if ( $scope instanceof Closure ) {
						return $scope( $builder );
					}

					if ( $scope instanceof Scope ) {
						return $scope->apply( $builder, $this->get_model_instance() );
					}
				}
			);
		}

		return $this;
	}

	/**
	 * Register a new global scope.
	 *
	 * @param  string        $identifier Scope name.
	 * @param Scope|\Closure $scope Scope callback.
	 * @return static
	 */
	public function with_global_scope( string $identifier, $scope ) {
		$this->scopes[ $identifier ] = $scope;

		return $this;
	}

	/**
	 * Set the limit of objects to include.
	 *
	 * @param int $limit Limit to set.
	 * @return static
	 */
	public function take( int $limit ) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Set the current page of the builder.
	 *
	 * @param int $page Page to set.
	 * @return static
	 */
	public function page( int $page ) {
		$this->page = $page;
		return $this;
	}

	/**
	 * Get the first result of the query.
	 *
	 * @return \Mantle\Database\Model|null
	 */
	public function first() {
		return $this->take( 1 )->get()[0] ?? null;
	}

	/**
	 * Get all the results of a query.
	 *
	 * @return Collection
	 */
	public function all(): Collection {
		return $this->take( -1 )->get();
	}

	/**
	 * Delete the results of this query.
	 *
	 * @param bool $force Flag to force delete.
	 * @return void
	 */
	public function delete( bool $force = false ) {
		$this->all()->each->delete( $force );
	}

	/**
	 * Execute the query and get the first result or throw an exception.
	 *
	 * @return \Mantle\Database\Model
	 * @throws Model_Not_Found_Exception Throws exception if not found.
	 */
	public function firstOrFail() {
		$model = $this->first();

		if ( ! $model ) {
			throw ( new Model_Not_Found_Exception() )->set_model( $this->model );
		}

		return $model;
	}

	/**
	 * Create a simple paginator instance for the current query.
	 *
	 * @param int $per_page Items per page.
	 * @param int $current_page Current page number.
	 * @return PaginatorContract
	 */
	public function simple_paginate( int $per_page = 20, int $current_page = null ): PaginatorContract {
		return Container::getInstance()->make_with(
			Paginator::class,
			[
				'builder'      => $this,
				'current_page' => $current_page,
				'per_page'     => $per_page,
			]
		);
	}

	/**
	 * Create a length-aware paginator instance for the current query.
	 *
	 * @param int $per_page Items per page.
	 * @param int $current_page Current page number.
	 * @return PaginatorContract
	 */
	public function paginate( int $per_page = 20, int $current_page = null ): PaginatorContract {
		return Container::getInstance()->make_with(
			Length_Aware_Paginator::class,
			[
				'builder'      => $this,
				'current_page' => $current_page,
				'per_page'     => $per_page,
			]
		);
	}

	/**
	 * Magic method to proxy to the appropriate query method.
	 *
	 * @param string $method Method name.
	 * @param array  $args Method arguments.
	 * @return mixed
	 *
	 * @throws Query_Exception Unknown query method called.
	 */
	public function __call( $method, $args ) {
		if ( Str::starts_with( $method, 'where' ) ) {
			return $this->dynamicWhere( $method, $args );
		}

		if ( Str::starts_with( $method, 'orderBy' ) ) {
			return $this->dynamicOrderBy( $method, $args );
		}

		// Check if the model has a local/named scope.
		if ( $this->has_named_scope( $method ) ) {
			return $this->call_named_scope( $method, $args );
		}

		throw new Query_Exception( 'Unknown query builder method: ' . $method );
	}

	/**
	 * Collect all the model object names in an associative Collection.
	 *
	 * @return Collection Collection with object names as keys and model
	 *                    class names as values.
	 */
	public function get_model_object_names(): Collection {
		return collect( (array) $this->model )
			->combine( $this->model )
			->map(
				function ( $model ) {
					return $model::get_object_name();
				}
			)
			->flip();
	}

	/**
	 * Begin a query with eager loading.
	 *
	 * @param string ...$relations Relations to eager load.
	 * @return static
	 */
	public function with( ...$relations ) {
		$this->eager_load = array_merge( $this->eager_load, $relations );
		return $this;
	}

	/**
	 * Begin a query without eager loading relationships.
	 *
	 * @param string ...$relations Relations to not eager load.
	 * @return static
	 */
	public function without( ...$relations ) {
		$this->eager_load = array_diff_key( $this->eager_load, array_flip( $relations ) );
		return $this;
	}

	/**
	 * Eager load relations for a set of models.
	 *
	 * @param Collection $models Models to load for.
	 * @return Collection
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
	 * @param Collection $models Model instances.
	 * @param string $name Relation name to eager load.
	 * @return Collection
	 */
	protected function eager_load_relation( Collection $models, string $name ) : Collection {
		$relation = $this->get_relation( $name );

		$results = Relation::no_constraints(
			function() use ( $models, $relation ) {
				// Add the eager constraints from the relation to the query.
				$relation->add_eager_constraints( $models );

				return $relation->get_eager();
			}
		);

		return $relation->match( $models, $results );
	}
}
