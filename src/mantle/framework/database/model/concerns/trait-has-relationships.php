<?php
/**
 * Has_Relationships trait file.
 *
 * @package Mantle
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Framework\Database\Model\Concerns;

use Mantle\Framework\Database\Model\Relations\Belongs_To;
use Mantle\Framework\Database\Model\Relations\Has_One_Or_Many;
use Mantle\Framework\Database\Model\Relations\Relation;

/**
 * Model Relationships
 */
trait Has_Relationships {
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

		return new Has_One_Or_Many( $instance->new_query(), $this, $foreign_key, $local_key );
	}

	/**
	 * Define a Has Many Relationship
	 *
	 * @param string $related Related model name.
	 * @param string $foreign_key Foreign key.
	 * @param string $local_key Local key.
	 * @return Relation
	 */
	public function has_many( ...$args ): Relation {
		return $this->has_one( ...$args );
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
}
