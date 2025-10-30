<?php
/**
 * Has_Relationships trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use InvalidArgumentException;
use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Model_Meta;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Relations\Belongs_To_Many;
use Mantle\Database\Model\Relations\Belongs_To;
use Mantle\Database\Model\Relations\Has_Many;
use Mantle\Database\Model\Relations\Has_One;
use Mantle\Database\Model\Relations\Relation;
use Mantle\Database\Model\Term;

/**
 * Model Relationships
 *
 * @template TModel of Core_Object&Model_Meta&Updatable&Model
 */
trait Has_Relationships {
	/**
	 * The loaded relationships for the model.
	 *
	 * @var array<string, Relation<TModel, Core_Object&Model_Meta&Updatable&Model>>
	 */
	protected $relations = [];

	/**
	 * Define a Has One Relationship
	 *
	 * @template TRelated of Core_Object&Model_Meta&Updatable&Model
	 *
	 * @param class-string<TRelated> $related Related model name.
	 * @param string                 $foreign_key Foreign key.
	 * @param string                 $local_key Local key.
	 * @return Has_One<TModel, TRelated>
	 */
	public function has_one( string $related, ?string $foreign_key = null, ?string $local_key = null ): Has_One {
		$instance      = new $related();
		$foreign_key ??= $this->get_foreign_key();
		$local_key   ??= $this->get_key_name();

		return new Has_One( $instance->new_query(), $this, $foreign_key, $local_key ); // @phpstan-ignore-line return.type
	}

	/**
	 * Define a Has Many Relationship
	 *
	 * @template TRelated of Core_Object&Model_Meta&Updatable&Model
	 *
	 * @param class-string<TRelated> $related Related model name.
	 * @param string                 $foreign_key Foreign key.
	 * @param string                 $local_key Local key.
	 * @return Has_Many<TModel, TRelated>
	 */
	public function has_many( string $related, ?string $foreign_key = null, ?string $local_key = null ): Has_Many {
		$instance      = new $related();
		$foreign_key ??= $this->get_foreign_key();
		$local_key   ??= $this->get_key_name();

		return new Has_Many( $instance->new_query(), $this, $foreign_key, $local_key ); // @phpstan-ignore-line return.type
	}

	/**
	 * Define a belongs to relationship.
	 *
	 * Defines a relationship between two models with the reference stored on the remote
	 * model's meta.
	 *
	 * @template TRelated of Core_Object&Model_Meta&Updatable&Model
	 *
	 * @param class-string<TRelated> $related Related model name.
	 * @param string                 $foreign_key Foreign key.
	 * @param string                 $local_key Local key.
	 * @return Belongs_To<TModel, TRelated>
	 *
	 * @throws InvalidArgumentException Used on the definition of a post and term relationship.
	 */
	public function belongs_to( string $related, ?string $foreign_key = null, ?string $local_key = null ): Belongs_To {
		// Check if this a post and term relationship.
		if (
			( $this instanceof Term && is_subclass_of( $related, Post::class ) )
			|| ( $this instanceof Post && is_subclass_of( $related, Term::class ) )
		) {
			throw new InvalidArgumentException( 'Post and term relationships must always use has_one() or has_many()' );
		}

		$instance      = new $related();
		$foreign_key ??= $this->get_key_name();
		$local_key   ??= $instance->get_foreign_key();

		return new Belongs_To( $instance->new_query(), $this, $foreign_key, $local_key ); // @phpstan-ignore-line return.type
	}

	/**
	 * Define a belongs to many relationship.
	 *
	 * Defines a relationship between two models with the reference stored on the remote
	 * object's meta.
	 *
	 * @template TRelated of Core_Object&Model_Meta&Updatable&Model
	 *
	 * @param class-string<TRelated> $related Related model name.
	 * @param string                 $foreign_key Foreign key.
	 * @param string                 $local_key Local key.
	 * @return Belongs_To_Many<TModel, TRelated>
	 *
	 * @throws InvalidArgumentException Used on the definition of a post and term relationship.
	 */
	public function belongs_to_many( string $related, ?string $foreign_key = null, ?string $local_key = null ): Belongs_To_Many {
		// Check if this a post and term relationship.
		if (
			( $this instanceof Term && is_subclass_of( $related, Post::class ) )
			|| ( $this instanceof Post && is_subclass_of( $related, Term::class ) )
		) {
			throw new InvalidArgumentException( 'Post and term relationships must always use has_one() or has_many()' );
		}

		$instance      = new $related();
		$foreign_key ??= $this->get_key_name();
		$local_key   ??= $instance->get_foreign_key();

		return new Belongs_To_Many( $instance->new_query(), $this, $foreign_key, $local_key ); // @phpstan-ignore-line return.type
	}

	/**
	 * Get a relationship for the model.
	 *
	 * @param string $relation Relation name.
	 * @return Relation<TModel, Core_Object&Model_Meta&Updatable&Model>|null
	 */
	public function get_relation( string $relation ): ?Relation {
		return $this->relations[ $relation ] ?? null;
	}

	/**
	 * Set a relationship for the model.
	 *
	 * @param string $relation Relation name.
	 * @param mixed  $value Value to set.
	 */
	public function set_relation( string $relation, mixed $value ): static {
		$this->relations[ $relation ] = $value;

		return $this;
	}

	/**
	 * Check if the given relation is loaded.
	 *
	 * @param string $relation Relation to check.
	 */
	public function relation_loaded( string $relation ): bool {
		return array_key_exists( $relation, $this->relations );
	}

	/**
	 * Unset a relationship for the model.
	 *
	 * @param string $relation Relation name.
	 */
	public function unset_relation( string $relation ): static {
		unset( $this->relations[ $relation ] );
		return $this;
	}
}
