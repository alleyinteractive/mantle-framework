<?php
/**
 * Modifies_Query trait file
 *
 * @package Mantle
 */

namespace Mantle\Database\Query\Concerns;

use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Database\Query\Term_Query_Builder;

/**
 * Allow a query to be modified.
 *
 * Provides an API for modifying the subsequent query performed by
 * WP_Query/WP_Term_Query/etc. in a controlled manner and does not affect other
 * queries.
 *
 * @mixin \Mantle\Database\Query\Builder
 */
trait Query_Clauses {
	/**
	 * Query clauses to be added to the query.
	 *
	 * @var array<int, callable(array<string>, \WP_Query|\WP_Term_Query)>>
	 */
	protected array $clauses = [];

	/**
	 * Add a clause to the query.
	 *
	 * @return static
	 */
	public function add_clause( callable $clause ): static {
		$this->clauses[] = $clause;

		return $this;
	}

	/**
	 * Remove all clauses from the query.
	 *
	 * @return static
	 */
	public function clear_clauses(): static {
		$this->clauses = [];

		return $this;
	}

	/**
	 * Apply the query clauses to the query.
	 *
	 * @param array<string> $clauses The query clauses.
	 * @param \WP_Query|\WP_Term_Query $query The query object.
	 * @return array<string> The modified query clauses.
	 */
	public function apply_clauses( array $clauses, \WP_Query|\WP_Term_Query $query ): array {
		// Only apply the clauses if the query hash matches.
		if ( spl_object_hash( $query ) !== $this->get_query_hash() ) {
			return $clauses;
		}

		foreach ( $this->clauses as $clause ) {
			$clauses = $clause( $clauses, $query );
		}

		return $clauses;
	}

	/**
	 * Register the query clauses before the query is executed.
	 */
	protected function register_clauses(): void {
		if ( $this instanceof Post_Query_Builder ) {
			add_filter( 'posts_clauses', [ $this, 'apply_clauses' ], 10, 2 );
		} elseif ( $this instanceof Term_Query_Builder ) {
			add_filter( 'terms_clauses', [ $this, 'apply_clauses' ], 10, 2 );
		}
	}

	/**
	 * Unregister the query clauses after the query is executed.
	 */
	protected function unregister_clauses(): void {
		if ( $this instanceof Post_Query_Builder ) {
			remove_filter( 'posts_clauses', [ $this, 'apply_clauses' ], 10 );
		} elseif ( $this instanceof Term_Query_Builder ) {
			remove_filter( 'terms_clauses', [ $this, 'apply_clauses' ], 10 );
		}
	}

	/**
	 * Execute a callback with the clauses applied only for the closure.
	 *
	 * @template TReturnValue
	 * @param callable(): TReturnValue $callback The callback to execute.
	 * @return TReturnValue The return value of the callback.
	 */
	public function with_clauses( callable $callback ): mixed {
		$this->register_clauses();

		$result = $callback();

		$this->unregister_clauses();

		return $result;
	}
}
