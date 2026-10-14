<?php
/**
 * Database_Query_Builder class file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment, WordPress.DB.DirectDatabaseQuery
 */

namespace Mantle\Database\Query;

use RuntimeException;

use function Mantle\Support\Helpers\collect;

/**
 * Database Query Builder
 *
 * @template TModel of \Mantle\Database\Model\Database_Table_Model
 * @extends \Mantle\Database\Query\Builder<TModel>
 */
class Database_Query_Builder extends Builder {
	/**
	 * Fields to select.
	 *
	 * @var string[]|null
	 */
	public array|null $select = null;

	/**
	 * Set the fields to select in the query.
	 *
	 * @param string[]|null $select Fields to select.
	 */
	public function select( array|null $select ): static {
		$this->select = $select;

		return $this;
	}

	/**
	 * Get the query arguments.
	 */
	public function get_query_args(): array {
		return [];
	}

	/**
	 * Execute the query.
	 *
	 * @throws RuntimeException If multiple models are used in the query.
	 *
	 * @return Collection<int, TModel>
	 */
	public function get(): Collection {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		/**
		 * @var array<array<string, mixed>>|null $query
		 */
		$query = $wpdb->get_results( $this->get_query_sql(), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( is_null( $query ) ) {
			return new Collection();
		}

		$model = $this->model;

		if ( is_array( $model ) ) {
			throw new RuntimeException(
				'Multiple models are not supported in the query builder. Use a single model or an array of models with eager loading.'
			);
		}

		return new Collection(
			array_map(
				fn ( array $row ) => $model::new_from_existing( $row ),
				$query
			)
		);
	}

	/**
	 * Get the count of the query results.
	 */
	public function count(): int {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$this->select( [ 'COUNT(*) AS count' ] );
		$this->take( -1 );

		return (int) $wpdb->get_var( $this->get_query_sql() ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get the SQL query for the current query.
	 *
	 * @throws RuntimeException If multiple models are used in the query.
	 */
	protected function get_query_sql(): string {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$this->apply_scopes();

		$select = $this->select ? implode( ', ', $this->select ) : '*';

		$wheres = collect( $this->bindings['where'] )
			->map( $this->compile_where_binding( ... ) )
			->filter()
			->values()
			->to_array();

		$limit = '';

		if ( -1 !== $this->limit ) {
			$limit = sprintf(
				'LIMIT %d, %d',
				$this->page !== 0 ? ( $this->page - 1 ) * $this->limit : 0,
				(int) $this->limit
			);
		}

		$model = $this->model;

		if ( is_array( $model ) ) {
			throw new RuntimeException(
				'Multiple models are not supported in the query builder. Use a single model or an array of models with eager loading.'
			);
		}

		$table = $wpdb->prefix . $model::get_table_name();

		$order = [];

		foreach ( $this->order_by as $index => $column ) {
			if ( ! preg_match( '/^[A-Za-z0-9_.]+$/', $column ) ) {
				throw new RuntimeException( 'Invalid column passed to order by.' );
			}

			$direction = 'DESC' === strtoupper( (string) ( $this->order[ $index ] ?? 'ASC' ) ) ? 'DESC' : 'ASC';

			$order[] = "{$column} {$direction}";
		}

		return sprintf(
			'SELECT %s FROM %s %s %s %s',
			$select,
			$table,
			count( $wheres ) > 0 ? 'WHERE ' . implode( ' ', $wheres ) : '',
			$order !== [] ? 'ORDER BY ' . implode( ', ', $order ) : '',
			$limit
		);
	}

	/**
	 * Compile a where binding into a SQL string.
	 *
	 * @param array<string, mixed> $binding Binding to compile.
	 * @param int|string           $index   Index of the binding in the where clause.
	 *
	 * @throws RuntimeException If the binding cannot be prepared.
	 */
	protected function compile_where_binding( array $binding, int|string $index ): string {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		if ( 'IN' === $binding['operator'] && is_array( $binding['value'] ) ) {
			$query = [] === $binding['value']
				? '1 = 0'
				: $wpdb->prepare(
					"{$binding['column']} IN (" . implode( ', ', array_fill( 0, count( $binding['value'] ), '%s' ) ) . ')', // phpcs:ignore WordPress.DB
					...array_values( $binding['value'] ),
				);
		} else {
			$query = $wpdb->prepare( "{$binding['column']} {$binding['operator']} %s", $binding['value'] ); // phpcs:ignore WordPress.DB
		}

		// Dropping a clause that failed to prepare would silently widen the query.
		if ( ! is_string( $query ) || '' === $query ) {
			throw new RuntimeException( sprintf( 'Unable to prepare the where clause for the "%s" column.', $binding['column'] ) );
		}

		return 0 === (int) $index ? $query : "{$binding['boolean']} {$query}";
	}

	/**
	 * Dump the SQL query being executed.
	 */
	public function dumpSql(): static {
		dump( $this->get_query_sql() );

		return $this;
	}

	/**
	 * Dump the SQL query being executed and die.
	 */
	public function ddSql(): never {
		$this->dumpSql();

		die( 1 );
	}

	/**
	 * Query where clause.
	 *
	 * @param array<string, mixed>|string $attribute Attribute to use or array of key => value attributes to set.
	 * @param mixed                       $value Value to set for the attribute if a single attribute is provided.
	 */
	#[\Override]
	public function where( array|string $attribute, mixed $value = '' ): static {
		if ( is_string( $attribute ) && is_array( $value ) ) {
			return $this->whereIn( $attribute, $value );
		}

		if ( is_array( $attribute ) && empty( $value ) ) {
			foreach ( $attribute as $key => $value ) {
				$this->where( $key, $value );
			}

			return $this;
		}

		return $this->where_raw( $attribute, '=', $value );
	}

	/**
	 * Add a query binding.
	 *
	 * @param array<string, mixed>|string $column The column name or array of bindings.
	 * @param string|null                 $operator The operator OR the value if no value is provided.
	 * @param mixed                       $value The value.
	 * @param string                      $boolean The boolean operator (AND/OR) used to concatenate the clause.
	 */
	#[\Override]
	public function where_raw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' ): static {
		if ( is_array( $column ) ) {
			foreach ( $column as $value ) {
				$this->where_raw( ...array_values( $value ) );
			}

			return $this;
		}

		$this->bindings['where'][] = [
			'boolean'  => $boolean,
			'column'   => $column,
			'operator' => $operator ?? '=',
			'value'    => $value,
		];

		return $this;
	}

	/**
	 * Query an attribute against a list.
	 *
	 * @param string $attribute Attribute to query against.
	 * @param array<mixed>  $values List of values.
	 */
	#[\Override]
	public function whereIn( string $attribute, array $values, string $boolean = 'AND' ): static {
		$this->bindings['where'][] = [
			'boolean'  => $boolean,
			'column'   => $attribute,
			'operator' => 'IN',
			'value'    => $values,
		];

		return $this;
	}
}
