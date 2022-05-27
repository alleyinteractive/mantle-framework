<?php
/**
 * Model_Term class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Term;

use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\Term;
use Mantle\Support\Arr;
use WP_Term;

use function Mantle\Support\Helpers\collect;

/**
 * Interface for interfacing with a model's terms.
 *
 * @property Model_Term_Proxy $terms Terms proxy instance.
 */
trait Model_Term {
	/**
	 * Terms queued for saving.
	 *
	 * @var array
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
	 * @param array $values Term values to set.
	 * @throws Model_Exception Thrown on invalid value being set.
	 */
	public function set_terms_attribute( $values ) {
		if ( ! is_array( $values ) ) {
			throw new Model_Exception( 'Attribute value passed to terms is not an array.' );
		}

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
	 * @return void
	 */
	public function queue_term_attribute( string $taxonomy, $value ): void {
		$this->queued_terms[ $taxonomy ] = $value;
	}

	/**
	 * Store queued model terms.
	 */
	protected function store_queued_terms() {
		foreach ( $this->queued_terms as $taxonomy => $values ) {
			$this->set_terms( $values, $taxonomy );
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
	 * @return static
	 *
	 * @throws Model_Exception Thrown if the $taxonomy cannot be inferred from $terms.
	 * @throws Model_Exception Thrown if error saving the post's terms.
	 */
	public function set_terms( $terms, string $taxonomy = null, bool $append = false ) {
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

		$update = \wp_set_object_terms( $this->id(), $terms, $taxonomy, $append );

		if ( \is_wp_error( $update ) ) {
			throw new Model_Exception( "Error setting model terms: [{$update->get_error_message()}]" );
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
	public function remove_terms( $terms, string $taxonomy = null ) {
		$terms = collect( is_array( $terms ) ? $terms : [ $terms ] )
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
