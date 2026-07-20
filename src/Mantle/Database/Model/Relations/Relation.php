<?php
/**
 * Relation class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Closure;
use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Model_Meta;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Database\Query\Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Database\Query\Term_Query_Builder;
use Mantle\Support\Collection;
use Mantle\Support\Forward_Calls;

/**
 * Relation base class.
 *
 * @template TParent of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
 * @template TModel of Core_Object&Model_Meta&Updatable&Model = Core_Object&Model_Meta&Updatable&Model
 *
 * @mixin \Mantle\Database\Query\Builder<TModel>
 */
abstract class Relation {
	use Forward_Calls;

	/**
	 * Internal taxonomy for post-to-post relationships.
	 *
	 * @var string
	 */
	public const RELATION_TAXONOMY = 'mantle_relationship';

	/**
	 * The related model (child).
	 */
	protected string $related;

	/**
	 * Flag if the relation uses terms.
	 */
	protected ?bool $uses_terms = null;

	/**
	 * Model's relationship name.
	 */
	protected ?string $relationship;

	/**
	 * Indicates if the relation is adding constraints.
	 *
	 * @var bool
	 */
	protected static $constraints = true;

	/**
	 * Create a new relation instance.
	 *
	 * @throws \InvalidArgumentException Thrown if the model on the query is an array.
	 *
	 * @param Builder<TModel> $query Query builder instance.
	 * @param Model<TParent>  $parent Model instance.
	 * @param bool|null       $uses_terms Flag if the relation uses terms.
	 * @param string          $relationship Relationship name, optional.
	 */
	public function __construct( protected Builder $query, protected Model $parent, ?bool $uses_terms = null, ?string $relationship = null ) {
		$related = $this->query->get_model();

		// Account for an edge condition that won't happen but PHPStan complains about.
		if ( is_array( $related ) ) {
			throw new \InvalidArgumentException( 'Related model must be a string, not an array.' );
		}

		$this->related = $related;

		if ( ! is_null( $uses_terms ) ) {
			$this->uses_terms( $uses_terms );
		}

		$this->relationship = $relationship ?: $this->guess_relationship();
	}

	/**
	 * Run a callback with constraints disabled on the relation.
	 *
	 * @param Closure $callback Callback to invoke.
	 * @return mixed
	 */
	public static function no_constraints( Closure $callback ) {
		$previous = static::$constraints;

		static::$constraints = false;

		try {
			return $callback();
		} finally {
			static::$constraints = $previous;
		}
	}


	/**
	 * Set the query constraints to apply to the query.
	 */
	abstract public function add_constraints(): void;

	/**
	 * Set the query constraints for an eager load of the relation.
	 *
	 * @param Collection<int, TParent> $models Models to eager load for.
	 */
	abstract public function add_eager_constraints( Collection $models ): void;

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	abstract public function get_results();

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param Collection<int, TParent> $models Parent models.
	 * @param Collection<int, TModel>  $results Eagerly loaded results to match.
	 * @return Collection<int, TParent>
	 */
	abstract public function match( Collection $models, Collection $results ): Collection;

	/**
	 * Retrieve the query for a relation.
	 *
	 * @return Builder<TModel>
	 */
	public function get_query(): Builder {
		return $this->query;
	}

	/**
	 * Get the relationship for eager loading.
	 *
	 * @return Collection<int, TModel>
	 */
	public function get_eager(): Collection {
		return $this->query->get();
	}

	/**
	 * Handle dynamic method calls to the relationship.
	 *
	 * @param string               $method Method name.
	 * @param array<string, mixed> $parameters Method arguments.
	 * @return mixed
	 */
	public function __call( string $method, array $parameters ) {
		$this->add_constraints();

		$result = $this->forward_call_to( $this->query, $method, $parameters );

		if ( $this->query === $result ) {
			return $this;
		}

		return $result;
	}

	/**
	 * Flag if the relation uses terms.
	 *
	 * @param bool $uses Flag if the relation uses or doesn't use terms.
	 * @return static
	 */
	public function uses_terms( bool $uses = true ) {
		$this->uses_terms = $uses;
		return $this;
	}

	/**
	 * Guess the name of the relationship.
	 *
	 * @todo Right now we're only limited to Post and Term models. This needs to
	 * be a check on a contract that defines the relationship methods (similar to
	 * Model_Meta). This method should be refactored once the contract is in place.
	 */
	protected function guess_relationship(): ?string {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		foreach ( $trace as $index => $item ) {
			if ( ! isset( $item['class'] ) ) {
				continue;
			}

			// If the class is Post/Term that means it is coming from the
			// Has_Relationships trait. We want the next item in the trace (the parent
			// calling class) to determine the relationship from.
			if (
				// TODO: Replace this with a contract check.
				( Post::class === $item['class'] || Term::class === $item['class'] )
				&& isset( $trace[ $index + 1 ] )
				&& isset( $trace[ $index + 1 ]['class'] )
				&& is_subclass_of( $item['class'], Model::class )
			) {
				return $trace[ $index + 1 ]['function'];
			}

			// If the next method in the trace isn't available/valid, keep proceeding
			// down the trace to find the lowest class that implements a post/term
			// model.
			// TODO: Replace this with a contract check.
			if ( is_subclass_of( $item['class'], Post::class ) || is_subclass_of( $item['class'], Term::class ) ) {
				$relationship = $item['function'];
			}
		}

		return $relationship ?? null;
	}

	/**
	 * Determine if this is a post -> term relationship.
	 */
	protected function is_post_term_relationship(): bool {
		return $this->parent instanceof Post && $this->query instanceof Term_Query_Builder;
	}

	/**
	 * Determine if this is a term -> post relationship.
	 */
	protected function is_term_post_relationship(): bool {
		return $this->parent instanceof Term && $this->query instanceof Post_Query_Builder;
	}
}
