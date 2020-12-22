<?php
/**
 * Has_Relationships trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model\Concerns;

use Mantle\Framework\Database\Model\Relations\Belongs_To;
use Mantle\Framework\Database\Model\Relations\Has_Many;
use Mantle\Framework\Database\Model\Relations\Has_One;
use Mantle\Framework\Database\Model\Relations\Has_One_Or_Many;
use Mantle\Framework\Database\Model\Relations\Relation;

/**
 * Model Relationships
 */
trait Has_Relationships {
	/**
	 * The loaded relationships for the model.
	 *
	 * @var array
	 */
	protected $relations = [];

	/**
	 * Define a Has One Relationship
	 *
	 * @param string $related Related model name.
	 * @param string $foreign_key Foreign key.
	 * @param string $local_key Local key.
	 * @return Relation
	 */
	public function has_one( string $related, string $foreign_key = null, string $local_key = null ): Relation {
		$instance    = new $related();
		$foreign_key = $foreign_key ?? $this->get_foreign_key();
		$local_key   = $local_key ?? $this->get_key_name();

		return new Has_One( $instance->new_query(), $this, $foreign_key, $local_key );
	}

	/**
	 * Define a Has Many Relationship
	 *
	 * @param string $related Related model name.
	 * @param string $foreign_key Foreign key.
	 * @param string $local_key Local key.
	 * @return Relation
	 */
	public function has_many( string $related, string $foreign_key = null, string $local_key = null ): Relation {
		$instance    = new $related();
		$foreign_key = $foreign_key ?? $this->get_foreign_key();
		$local_key   = $local_key ?? $this->get_key_name();

		return new Has_Many( $instance->new_query(), $this, $foreign_key, $local_key );
	}

	/**
	 * Define a belongs to relationship.
	 *
	 * Defines a relationship between two posts with the reference stored on the remote
	 * post's meta.
	 *
	 * @param string $related Related model name.
	 * @param string $foreign_key Foreign key.
	 * @param string $local_key Local key.
	 * @return Relation
	 */
	public function belongs_to( string $related, string $foreign_key = null, string $local_key = null ): Relation {
		$instance    = new $related();
		$foreign_key = $foreign_key ?? $this->get_key_name();
		$local_key   = $local_key ?? $instance->get_foreign_key();

		return new Belongs_To( $instance->new_query(), $this, $foreign_key, $local_key );
	}

	/**
	 * Get a relationship for the model.
	 *
	 * @param string $relation Relation name.
	 * @return Relation|null
	 */
	public function get_relation( string $relation ): ?Relation {
		return $this->relations[ $relation ] ?? null;
	}

	/**
	 * Set a relationship for the model.
	 *
	 * @param string $relation Relation name.
	 * @param mixed  $value Value to set.
	 * @return static
	 */
	public function set_relation( string $relation, $value ) {
		$this->relations[ $relation ] = $value;

		return $this;
	}

	/**
	 * Unset a relationship for the model.
	 *
	 * @param string $relation Relation name.
	 * @return static
	 */
	public function unset_relation( string $relation ) {
		unset( $this->relations[ $relation ] );
		return $this;
	}
}
