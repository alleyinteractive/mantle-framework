<?php
/**
 * Has_One_Or_Many class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Model\Model_Exception;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Database\Query\Builder;
use Mantle\Framework\Database\Query\Post_Query_Builder;
use Mantle\Framework\Database\Query\Term_Query_Builder;
use Mantle\Framework\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Has One or Many Relationship
 */
abstract class Has_One_Or_Many extends Relation {
	/**
	 * Delimiter for the term slug.
	 *
	 * @var string
	 */
	public const DELIMITER = '__-__';

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
		if ( static::$constraints ) {
			if ( $this->uses_terms ) {
				return $this->query->whereTerm( $this->get_term_slug_for_relationship(), static::RELATION_TAXONOMY );
			} else {
				return $this->query->whereMeta( $this->foreign_key, $this->parent->get( $this->local_key ) );
			}
		}
	}

	/**
	 * Set the query constraints for an eager load of the relation.
	 *
	 * @param Collection $models Models to eager load for.
	 * @return void
	 *
	 * @throws RuntimeException Thrown on currently unsupported query condition.
	 */
	public function add_eager_constraints( Collection $models ): void {
		$keys = $models->pluck( $this->local_key )->to_array();

		if ( $this->uses_terms ) {
			throw new RuntimeException( 'Eager loading relationships with terms is not supported yet.' );
		} else {
			$this->query->whereMeta( $this->foreign_key, $keys, 'IN' );
		}
	}

	/**
	 * Attach a model to a parent model and save it.
	 *
	 * @param Model[]|Model $model Model instance to save.
	 * @return Model
	 */
	public function save( $model ): Model {
		if ( $this->is_post_term_relationship() ) {
			$this->parent->set_terms( $model );
		} elseif ( $this->is_term_post_relationship() ) {
			$models = is_array( $model ) ? $model : [ $model ];

			foreach ( $models as $model ) {
				$model->set_terms( $this->parent );
			}
		} else {
			// Save the model if it doesn't exist.
			if ( ! $model->exists ) {
				$model->save();
			}

			// Set meta or use a hidden taxonomy if using terms.
			if ( $this->uses_terms ) {
				wp_set_post_terms( $model->id(), [ $this->get_term_for_relationship() ], static::RELATION_TAXONOMY, true );
			} else {
				$model->set_meta( $this->foreign_key, $this->parent->get( $this->local_key ) );
			}
		}

		if ( $this->relationship ) {
			$this->parent->unset_relation( $this->relationship );
		}

		return $model;
	}

	/**
	 * Dissociate a model from a parent model.
	 *
	 * @param Model $model Model instance to save.
	 * @return Model
	 */
	public function remove( Model $model ): Model {
		if ( $this->is_post_term_relationship() ) {
			$this->parent->remove_terms( $model );
		} elseif ( $this->is_term_post_relationship() ) {
			$models = is_array( $model ) ? $model : [ $model ];

			foreach ( $models as $model ) {
				$model->remove_terms( $this->parent );
			}
		} elseif ( $model->exists ) {
			if ( $this->uses_terms ) {
				$term = $this->get_term_for_relationship();

				if ( has_term( $term, static::RELATION_TAXONOMY, $model->id() ) ) {
					wp_remove_object_terms( $model->id(), $term, static::RELATION_TAXONOMY );
				}
			} else {
				$model->delete_meta( $this->foreign_key );
			}
		}

		if ( $this->relationship ) {
			$this->parent->unset_relation( $this->relationship );
		}

		return $model;
	}

	/**
	 * Determine if this is a post -> term relationship.
	 *
	 * @return bool
	 */
	protected function is_post_term_relationship(): bool {
		return $this->parent instanceof Post && $this->query instanceof Term_Query_Builder;
	}

	/**
	 * Determine if this is a term -> post relationship.
	 *
	 * @return bool
	 */
	protected function is_term_post_relationship(): bool {
		return $this->parent instanceof Term && $this->query instanceof Post_Query_Builder;
	}

	/**
	 * Retrieve a internal term for a post-to-post relationship.
	 *
	 * @return int
	 * @throws Model_Exception Thrown on error using internal term with a post to term or term to post relationship.
	 */
	protected function get_term_for_relationship(): int {
		if ( $this->is_post_term_relationship() || $this->is_term_post_relationship() ) {
			throw new Model_Exception( 'Unable to retrieve an internal term for a post->term or term->post relationship.' );
		}

		$name = $this->get_term_slug_for_relationship();
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
	 * Retrieve the term slug for a post-to-post relationship.
	 *
	 * @return string
	 */
	protected function get_term_slug_for_relationship(): string {
		$delimiter = static::DELIMITER;
		return "{$this->foreign_key}{$delimiter}{$this->parent->get( $this->local_key )}";
	}

	/**
	 * Build a model dictionary keyed by the relation's foreign key.
	 *
	 * @param Collection $results Collection of results.
	 * @return array
	 */
	protected function build_dictionary( Collection $results ): array {
		return $results
			->map_to_dictionary(
				function ( $result ) {
					try {
						return [ $result->meta->{$this->foreign_key} => $result ];
					} catch ( Throwable $e ) {
						return [];
					}
				}
			)
			->all();
	}
}
