<?php
/**
 * Has_One_Or_Many class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Query\Builder;

/**
 * Has One or Many Relationship
 */
class Has_One_Or_Many extends Relation {
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
		return $this->query->where( $this->local_key, $this->parent->get_meta( $this->foreign_key ) );
	}

	/**
	 * Attach a model to a parent model and save it.
	 *
	 * @param Model $model Model instance to save.
	 * @return Model
	 */
	public function save( Model $model ): Model {
		$this->set_foreign_attributes_for_create( $model );
		return $model->save() ? $model : false;
	}

	/**
	 * Set foreign attributes on the save model method.
	 *
	 * @param Model $model Model instance to set on.
	 * @return Model
	 */
	protected function set_foreign_attributes_for_create( Model $model ): Model {
		var_dump('model to save', $this->foreign_key, $this->local_key );exit;
		// $model->set_meta( $this->foreign_key );
	}
}
