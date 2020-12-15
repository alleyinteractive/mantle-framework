<?php
/**
 * Belongs_To class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Query\Builder;
use Mantle\Framework\Database\Query\Term_Query_Builder;
use Mantle\Framework\Support\Str;
use WP_Term;

use function Mantle\Framework\Helpers\collect;

/**
 * Creates a 'Belongs To' relationship.
 * Performs a meta query on the parent model with data from the current model.
 *
 * Example: Search the parent post's meta query with the ID of the current model.
 */
class Belongs_To extends Relation {
	/**
	 * Local key.
	 *
	 * @var string
	 */
	protected $local_key;

	/**
	 * Foreign key.
	 *
	 * @var string
	 */
	protected $foreign_key;

	/**
	 * Create a new has one or many relationship instance.
	 *
	 * @param Builder $query Query builder object.
	 * @param Model   $parent Parent model.
	 * @param string  $foreign_key Foreign key.
	 * @param string  $local_key Local key.
	 */
	public function __construct( Builder $query, Model $parent, string $foreign_key, ?string $local_key = null ) {
		$this->foreign_key = $foreign_key;
		$this->local_key   = $local_key;

		parent::__construct( $query, $parent );
	}

	/**
	 * Add constraints to the query.
	 */
	public function add_constraints() {
		if ( $this->uses_terms ) {
			$object_ids = $this->get_term_ids_for_relationship();

			if ( empty( $object_ids ) ) {
				// Prevent the query from going through.
				// @todo Handle this better.
				return $this->query->where( 'id', PHP_INT_MAX );
			} else {
				return $this->query->whereIn( 'id', $object_ids );
			}
		} else {
			$meta_value = $this->parent->get_meta( $this->local_key );

			if ( empty( $meta_value ) ) {
				/**
				 * Prevent the query from going through.
				 *
				 * @todo Handle missing meta value better.
				 */
				$this->query->where( 'id', PHP_INT_MAX );
			} else {
				$this->query->where( $this->foreign_key, $meta_value );
			}
		}

		return $this->query;
	}

	/**
	 * Associate a model with a relationship.
	 *
	 * @param Model $model Model to save to.
	 * @return Model
	 *
	 * @throws Model_Exception Thrown on error setting term for relationship.
	 */
	public function associate( Model $model ) {
		if ( ! $model->exists ) {
			$model->save();
		}

		if ( $this->uses_terms ) {
			$set = wp_set_post_terms( $this->parent->id(), [ $this->get_term_for_relationship( $model ) ], static::RELATION_TAXONOMY, true );

			if ( is_wp_error( $set ) ) {
				throw new Model_Exception( "Error associating term relationship for [{$this->parent->id()}]: [{$set->get_error_message()}]" );
			} elseif ( false === $set ) {
				throw new Model_Exception( "Unknown error associating term relationship for [{$this->parent->id()}]" );
			}
		} else {
			$this->parent->set_meta( $this->local_key, $model->id() );
		}

		return $model;
	}

	/**
	 * Proxy to `Belongs_To::associate()`.
	 *
	 * @param Model $model Model to save to.
	 * @return Model
	 */
	public function save( Model $model ) {
		return $this->associate( $model );
	}

	/**
	 * Remove the relationship from the model.
	 *
	 * @return static
	 */
	public function dissociate() {
		if ( $this->uses_terms ) {
			$term_ids = $this->get_term_ids_for_relationship( true );

			if ( ! empty( $term_ids ) ) {
				wp_remove_object_terms( $this->parent->id(), $term_ids, static::RELATION_TAXONOMY );
			}
		} else {
			$this->parent->delete_meta( $this->local_key );
		}

		return $this;
	}

	/**
	 * Add the query constraints for querying against the relationship.
	 *
	 * @param Builder $builder Query builder instance.
	 * @param string  $compare_value Value to compare against, optional.
	 * @param string  $compare Comparison operator (=, >, EXISTS, etc.).
	 * @return Builder
	 *
	 * @throws Model_Exception Thrown on unsupported relationship method.
	 */
	public function get_relation_query( Builder $builder, $compare_value = null, string $compare = 'EXISTS' ): Builder {
		if ( $this->uses_terms ) {
			throw new Model_Exception( 'Queries_Relationships does not support post <--> post relationships with terms.' );
		}

		if ( $compare_value ) {
			return $builder->whereMeta( $this->local_key, $compare_value, $compare ?? '' );
		}

		return $builder->whereMeta( $this->local_key, '', $compare );
	}

	/**
	 * Retrieve a internal term for a post to post relationship.
	 *
	 * @param Model|string $model Model instance/id.
	 * @return int
	 * @throws Model_Exception Thrown on error creating internal term with a post to term or term to post relationship.
	 */
	protected function get_term_for_relationship( $model ): int {
		$name = $this->get_term_slug_for_relationship( $model );
		$term = get_term_by( 'name', $name, static::RELATION_TAXONOMY );

		if ( empty( $term ) ) {
			$insert = wp_insert_term( $name, static::RELATION_TAXONOMY );

			if ( is_wp_error( $insert ) ) {
				throw new Model_Exception( "Error creating internal term for relationship: [{$insert->get_error_message()}]" );
			}

			return $insert['term_id'];
		}

		return $term->term_id;
	}

	/**
	 * Retrieve the term slug for a post to post relationship.
	 *
	 * @param Model|string $model Model instance/id.
	 * @return string
	 */
	protected function get_term_slug_for_relationship( $model ): string {
		$delimiter = Has_One_Or_Many::DELIMITER;
		$model     = $model instanceof Model ? $model->get( $this->foreign_key ) : $model;

		return "{$this->local_key}{$delimiter}{$model}";
	}

	/**
	 * Retrieve term IDs from the current parent for the relationship.
	 *
	 * @param bool $return_term_ids Flag to return the term ID of the relationship
	 *                              term, parent ID otherwise.
	 * @return int[]|string[]
	 */
	protected function get_term_ids_for_relationship( bool $return_term_ids = false ): array {
		$object_terms = get_the_terms( $this->parent->id(), static::RELATION_TAXONOMY );

		if ( empty( $object_terms ) || is_wp_error( $object_terms ) ) {
			return [];
		}

		return collect( $object_terms )
			->filter(
				function( WP_Term $term ) {
					$key = Str::before_last( $term->slug, Has_One_Or_Many::DELIMITER );
					$id  = Str::after_last( $term->slug, Has_One_Or_Many::DELIMITER );

					return $key === $this->local_key && ! empty( $id );
				}
			)
			->map(
				function( WP_Term $term ) use ( $return_term_ids ) {
					if ( $return_term_ids ) {
						return $term->term_id;
					}

					return (int) Str::after_last( $term->slug, Has_One_Or_Many::DELIMITER );
				}
			)
			->values()
			->to_array();
	}
}
