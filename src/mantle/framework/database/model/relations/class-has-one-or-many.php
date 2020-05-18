<?php
namespace Mantle\Framework\Database\Model\Relations;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Database\Query\Builder;

class Has_One_Or_Many extends Relation {
	/**
	 * The foreign key of the parent model.
	 *
	 * @var string
	 */
	protected $foreign_key;

	/**
	 * The local key of the parent model.
	 *
	 * @var string
	 */
	protected $local_key;

	/**
	 * Create a new has one or many relationship instance.
	 *
	 * @param Builder $query
	 * @param Model   $parent
	 * @param string  $foreignKey
	 * @param string  $localKey
	 */
	public function __construct( Builder $query, Model $parent, string $foreign_key, $local_key ) {
		$this->local_key   = $local_key;
		$this->foreign_key = $foreign_key;

		parent::__construct( $query, $parent );
	}

	public function add_constraints() {
		$this->query->where( $this->foreign_key, $this->get_parent_key() );
	}
}
