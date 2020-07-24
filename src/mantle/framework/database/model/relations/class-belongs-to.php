<?php
/**
 * Belongs_To class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Query\Builder;
use Mantle\Framework\Database\Query\Term_Query_Builder;

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

		return $this->query;
	}

	/**
	 * Associate a model with a relationship.
	 *
	 * @param Model $model Model to save to.
	 * @return static
	 */
	public function associate( Model $model ) {
		$this->parent->set_meta( $this->local_key, $model->id() );
		return $this;
	}

	/**
	 * Remove the relationship from the model.
	 *
	 * @return static
	 */
	public function dissociate() {
		$this->parent->delete_meta( $this->local_key );
		return $this;
	}

	/**
	 * Add the query constraints for querying against the relationship.
	 *
	 * @param Builder $builder Query builder instance.
	 * @param string  $compare_value Value to compare against, optional.
	 * @param string  $compare Comparison operator (=, >, EXISTS, etc.).
	 * @return Builder
	 */
	public function get_relation_query( Builder $builder, $compare_value = null, string $compare = 'EXISTS' ): Builder {
		if ( $compare_value ) {
			return $builder->whereMeta( $this->local_key, $compare_value, $compare ?? '' );
		}

		return $builder->whereMeta( $this->local_key, '', $compare );
	}
}
