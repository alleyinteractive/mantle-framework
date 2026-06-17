<?php
/**
 * Model_Term class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Term;

use InvalidArgumentException;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Term;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use WP_Term;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\get_term_object;

/**
 * Interface for interfacing with a model's terms.
 *
 * @property Model_Term_Proxy $terms Proxy to manage terms for the model.
 */
trait Model_Term {
	/**
	 * Terms queued for saving.
	 *
	 * @var array<mixed>
	 */
	protected $queued_terms = [];

	/**
	 * Retrieve the terms 'attribute'.
	 */
	public function get_terms_attribute(): \Mantle\Database\Model\Term\Model_Term_Proxy {
		return new Model_Term_Proxy( $this );
	}

	/**
	 * Allow setting terms through an array via an attribute mutator.
	 *
	 * @param array<mixed> $values Term values to set.
	 */
	public function set_terms_attribute( array $values ): void {
		$this->queued_terms = $values;
	}

	/**
	 * Get a queued term attribute.
	 *
	 * @param string $key Taxonomy key.
	 * @return mixed|null Terms or null.
	 */
	public function get_queued_term_attribute( string $key ) {
		return ( $this->queued_terms[ $key ] ?? [] )[0] ?? null;
	}

	/**
	 * Queue a term for saving
	 * Allows terms to be set before a post is saved.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param mixed  $value Terms.
	 */
	public function queue_term_attribute( string $taxonomy, $value ): void {
		$this->queued_terms[ $taxonomy ] = $value;
	}

	/**
	 * Store queued model terms.
	 */
	protected function store_queued_terms(): void {
		if ( empty( $this->queued_terms ) ) {
			return;
		}

		// Determine if this is an array of terms instead of taxonomy => term pairs.
		if ( Arr::is_assoc( $this->queued_terms ) ) {
			foreach ( $this->queued_terms as $taxonomy => $values ) {
				$this->set_terms( $values, $taxonomy );
			}
		} else {
			$this->set_terms( $this->queued_terms );
		}

		$this->queued_terms = [];
	}

	/**
	 * Get term(s) associated with a post.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return Term[]
	 */
	public function get_terms( string $taxonomy ): array {
		$terms = \get_the_terms( $this->id(), $taxonomy );

		if ( empty( $terms ) || \is_wp_error( $terms ) ) {
			return [];
		}

		return array_map(
			fn ( WP_Term $term ) => Term::new_from_existing( (array) $term ),
			(array) $terms,
		);
	}

	/**
	 * Set the term(s) associated with a post.
	 *
	 * @param mixed  $terms Accepts an array of or a single instance of terms.
	 * @param string $taxonomy Taxonomy name, optional.
	 * @param bool   $append Append to the object's terms, defaults to false.
	 * @param bool   $create Create the term if it does not exist, defaults to false.
	 * @return static
	 *
	 * @throws Model_Exception Thrown if the $taxonomy cannot be inferred from $terms.
	 * @throws Model_Exception Thrown if error saving the post's terms.
	 */
	public function set_terms( $terms, ?string $taxonomy = null, bool $append = false, bool $create = false ) {
		$terms = collect( Arr::wrap( $terms ) );

		if ( $taxonomy ) {
			$terms = $terms->map( function ( mixed $term ) use ( $taxonomy ): int {
				if ( $term instanceof WP_Term || $term instanceof Term ) {
					return $term->term_id;
				}

				if ( is_string( $term ) ) {
					$term = get_term_by( 'slug', $term, $taxonomy );

					if ( $term instanceof WP_Term ) {
						return $term->term_id;
					}
				}

				if ( ! is_numeric( $term ) ) {
					throw new InvalidArgumentException(
						"Invalid term value passed to set_terms (expected Term/WP_Term/int): {$term}",
					);
				}

				return (int) $term;
			} )->filter()->values()->all();

			$update = \wp_set_object_terms( $this->id(), $terms, $taxonomy, $append );

			if ( \is_wp_error( $update ) ) {
				throw new Model_Exception( "Error setting model terms: [{$update->get_error_message()}]" );
			}

			return $this;
		}

		// If a taxonomy was not passed, we need to infer it from the terms. This is
		// a bit tricky since we need to support both a single taxonomy and multiple
		// taxonomies. Thankfully, we have tests.
		$terms = $terms->reduce(
			fn ( array $carry, $argument, $parent_index ) => $this->resolve_mixed_term( $carry, $argument, $parent_index, $create ),
			[],
		);

		foreach ( collect( $terms )->filter() as $taxonomy => $items ) {
			$this->set_terms( Arr::pluck( $items, 'term_id' ), $taxonomy, $append );
		}

		return $this;
	}

	/**
	 * Resolve a term from a mixed value.
	 *
	 * @throws Model_Exception Thrown if the term cannot be created.
	 *
	 * @param array<string, WP_Term[]> $carry Array of terms to resolve.
	 * @param mixed                    $value Term value to resolve. Supports Term, WP_Term, int, string, or array of each.
	 * @param string|null              $taxonomy Taxonomy name, optional.
	 * @param bool                     $create Create the term if it does not exist, defaults to false.
	 * @return array<string, WP_Term[]>
	 */
	private function resolve_mixed_term( array $carry, mixed $value, ?string $taxonomy = null, bool $create = false ): array {
		if ( $value instanceof WP_Term || $value instanceof Term ) {
			$object = $value instanceof Term ? $value->core_object() : $value;

			if ( $object instanceof WP_Term ) {
				$carry[ $object->taxonomy ][] = $object;
			}

			return $carry;
		}

		if ( ! is_string( $taxonomy ) || is_numeric( $taxonomy ) ) {
			$taxonomy = null;
		}

		if ( is_numeric( $value ) ) {
			$term = get_term_object( (int) $value, $taxonomy ?: '' );

			if ( $term instanceof \WP_Term ) {
				$carry[ $term->taxonomy ][] = $term;
			}

			return $carry;
		}

		if ( is_string( $value ) ) {
			$term = get_term_by( 'slug', $value, $taxonomy ?? '' );

			if ( ! $term && $create ) {
				// Skip creating a term if a taxonomy was not passed.
				if ( ! is_string( $taxonomy ) ) {
					return $carry;
				}

				$term = wp_insert_term( Str::headline( $value ), $taxonomy, [ 'slug' => $value ] );

				if ( is_wp_error( $term ) ) {
					throw new Model_Exception( "Error creating term: [{$term->get_error_message()}]" );
				}

				$term = get_term_object( $term['term_id'], $taxonomy );
			}

			if ( $term ) {
				$carry[ $term->taxonomy ][] = $term;
			}

			return $carry;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$sub_taxonomy = is_string( $index ) ? $index : $taxonomy;

				$carry = $this->resolve_mixed_term( $carry, $item, $sub_taxonomy, $create );
			}
		}

		return $carry;
	}

	/**
	 * Remove terms from a post.
	 *
	 * @param mixed  $terms Accepts an array of or a single instance of terms.
	 * @param string $taxonomy Taxonomy name, optional.
	 * @return static
	 *
	 * @throws Model_Exception Thrown if the $taxonomy cannot be inferred from $terms.
	 */
	public function remove_terms( $terms, ?string $taxonomy = null ) {
		$terms = collect( Arr::wrap( $terms ) )
			->map(
				function ( $term ) use ( &$taxonomy ) {
					if ( $term instanceof Term ) {
						if ( empty( $taxonomy ) ) {
							$taxonomy = $term->taxonomy();
						}

						return $term->id();
					}

					if ( $term instanceof \WP_Term ) {
						if ( empty( $taxonomy ) ) {
							$taxonomy = $term->taxonomy;
						}

						return $term->term_id;
					}

					return $term;
				}
			)
			->filter()
			->all();

		if ( empty( $taxonomy ) ) {
			throw new Model_Exception( 'Term taxonomy not able to be inferred.' );
		}

		\wp_remove_object_terms( $this->id(), $terms, $taxonomy );

		return $this;
	}
}
