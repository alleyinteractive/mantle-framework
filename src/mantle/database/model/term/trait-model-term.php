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
use function Mantle\Support\Helpers\get_term_object_by;

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
	 *
	 * @return Model_Term_Proxy
	 */
	public function get_terms_attribute() {
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
	protected function store_queued_terms() {
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

		// If a taxonomy was not passed, we need to infer it from the terms.
		// This is a bit tricky since we need to support both a single taxonomy
		// and multiple taxonomies. Thankfully, we have tests.
		$terms = $terms->reduce(
			function ( array $carry, $argument, $parent_index ) use ( $create ): array {
				$argument = Arr::wrap( $argument );

				foreach ( $argument as $index => $item ) {
					if ( $item instanceof WP_Term || $item instanceof Term ) {
						$carry[ $item->taxonomy ][] = $item instanceof Term
							? $item->core_object()
							: $item;

						continue;
					}

					$taxonomy = match ( true ) {
						is_string( $index ) => $index,
						is_string( $parent_index ) => $parent_index,
						default => null,
					};

					// Support an array of term slugs.
					if ( is_array( $item ) ) {
						foreach ( $item as $sub_index => $slug ) {
							if ( ! $taxonomy && $sub_index ) {
								$taxonomy = $sub_index;
							}

							if ( is_numeric( $slug ) ) {
								$term = get_term_object( (int) $slug );

								if ( $term instanceof WP_Term ) {
									$carry[ $term->taxonomy ][] = $term;
								}

								continue;
							}

							if ( ! is_string( $slug ) ) {
								throw new Model_Exception(
									'Invalid array sub-item passed to set_terms (expected term slug): ' .
									print_r( $slug, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
								);
							}

							$term = get_term_by( 'slug', $slug, $taxonomy ?? '' );

							if ( ! $term && $create ) {
								$term = wp_insert_term( Str::headline( $slug ), $taxonomy, [ 'slug' => $slug ] );

								if ( is_wp_error( $term ) ) {
									throw new Model_Exception( "Error creating term: [{$term->get_error_message()}]" );
								}

								$term = get_term( $term['term_id'], $taxonomy );
							}

							if ( $term ) {
								$carry[ $term->taxonomy ][] = $term;
							}
						}

						continue;
					}

					if ( ! is_numeric( $item ) && ! is_string( $item ) ) {
						throw new InvalidArgumentException(
							sprintf(
								'Invalid term value passed to set_terms (expected Term/WP_Term/int/string): %s',
								gettype( $item ),
							),
						);
					}

					// Support an array of taxonomy => term ID/slug pairs.
					if ( is_numeric( $item ) ) {
						$term = get_term_object( (int) $item );

						if ( $term instanceof WP_Term ) {
							$carry[ $term->taxonomy ][] = $term;
						}

						continue;
					}

					// Ensure a taxonomy was valid if passed.
					if ( is_string( $taxonomy ) && ! taxonomy_exists( $taxonomy ) ) {
						throw new Model_Exception(
							"Invalid taxonomy passed to set_terms (expected taxonomy string): {$taxonomy}",
						);
					}

					$term = get_term_object_by( 'slug', $item, $taxonomy ?? '' );

					// Optionally create the term if it does not exist.
					if ( ! $term && $create ) {
						// Skip creating a term if a taxonomy was not passed.
						if ( ! is_string( $taxonomy ) ) {
							continue;
						}

						$term = wp_insert_term( Str::headline( $item ), $taxonomy, [ 'slug' => $item ] );

						if ( is_wp_error( $term ) ) {
							throw new Model_Exception( "Error creating term: [{$term->get_error_message()}]" );
						}

						$term = get_term( $term['term_id'], $index );
					}

					if ( $term instanceof WP_Term ) {
						$carry[ $index ][] = $term;
					}
				}

				return $carry;
			},
			[],
		);

		foreach ( collect( $terms )->filter() as $taxonomy => $items ) {
			$this->set_terms( Arr::pluck( $items, 'term_id' ), $taxonomy, $append );
		}

		return $this;
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
