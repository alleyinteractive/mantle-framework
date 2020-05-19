<?php
/**
 * Builder class file.
 *
 * @package Mantle
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Framework\Database\Query;

use Mantle\Framework\Support\Collection;
use Mantle\Framework\Support\Str;

/**
 * Builder Query Builder
 */
abstract class Builder {
	/**
	 * Model to build on.
	 *
	 * @var string
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
	 * Constructor.
	 *
	 * @param string $model Model class name.
	 */
	public function __construct( string $model ) {
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
	 * @return string
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Query an attribute against a list.
	 *
	 * @param string $attribute Attribute to query against.
	 * @param array  $values List of values.
	 * @return static
	 */
	public function whereIn( string $attribute, array $values ) {
		if ( $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_in_aliases[ strtolower( $attribute ) ];
		}

		return $this->where( $attribute, (array) $values );
	}

	/**
	 * Query an where an attribute is not in a list.
	 *
	 * @param string $attribute Attribute to query against.
	 * @param array  $values List of values.
	 * @return static
	 */
	public function whereNotIn( string $attribute, array $values ) {
		if ( $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_not_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_not_in_aliases[ strtolower( $attribute ) ];
		}

		return $this->where( $attribute, $values );
	}

	/**
	 * Create a query builder for a model.
	 *
	 * @param string $model Model name.
	 * @return static
	 */
	public static function create( string $model ) {
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
		if ( $this->model::has_attribute_alias( $attribute ) ) {
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
		$this->meta_query[] = [
			'compare' => $compare,
			'key'     => $key,
			'value'   => $value,
		];
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
		if ( $this->model::has_attribute_alias( $attribute ) ) {
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
	 * Get the first result of the query.
	 *
	 * @return \Mantle\Framework\Database\Model|null
	 */
	public function first() {
		return $this->take( 1 )->get()[0] ?? null;
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

		throw new Query_Exception( 'Unknown query builder method: ' . $method );
	}
}
