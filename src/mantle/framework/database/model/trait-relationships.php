<?php
/**
 * Relationships trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use Mantle\Framework\Database\Model\Relations\Has_One;
use Mantle\Framework\Database\Query\Post_Query_Builder;

/**
 * Model Relationships
 */
trait Relationships {
	/**
	 * Define a 'has one' relationship.
	 *
	 * Defines a relationship between two posts where the relationship meta is stored
	 * in the local post's meta.
	 *
	 * @param string $related Related model name.
	 * @param string $local_meta_key Local meta key storing the ID of the remote model.
	 * @param string $foreign_key Foreign key name, defaults to the model's primary key.
	 * @return \Mantle\Framework\Database\Query\Builder
	 */
	public function has_one( string $related, string $local_meta_key, string $foreign_key = '' ) {
		$builder = $related::get_query_builder_class();
		$builder = new $builder( $related );

		$foreign_key = $foreign_key ?: ( new $related() )->get_key_name();

		return $builder->where( $foreign_key, $this->get_meta( $local_meta_key ) );
	}

	/**
	 * Define a belongs to relationship.
	 *
	 * Defines a relationship between two posts with the reference stored on the remote
	 * post's meta.
	 *
	 * @param string $related Related model name.
	 * @param string $remote_meta_key Remote meta key.
	 * @param string $local_key Local key to compare against, defaults to primary.
	 * @return \Mantle\Framework\Database\Query\Builder
	 */
	public function belongs_to( string $related, string $remote_meta_key, string $local_key = '' ) {
		$builder = $related::get_query_builder_class();
		$builder = new $builder( $related );

		$local_key = $local_key ?: (new $related() )->get_key_name();

		return $builder->whereMeta( $remote_meta_key, $this->get( $local_key ) );
	}

	// public function hasMany( string $related ) {

	// }

	// public function hasOneCustom( string $related, array $args ) {

	// }
}
