<?php
/**
 * Term class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use Mantle\Framework\Contracts\Database\Core_Object;
use Mantle\Framework\Database\Model\Meta\Model_Meta as Model_With_Meta;

/**
 * Term Model
 */
class Term extends Model_With_Meta implements Core_Object {
	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected static $aliases = [
		'id' => 'term_id',
	];

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object ) {
		$this->attributes = (array) $object;
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param int $object_id Object ID.
	 * @return Model|null
	 */
	public static function find( $object_id ): ?Model {
		$post = \get_term( $object_id );
		return $post ? new static( $post ) : null;
	}

	/**
	 * Get the meta type for the object.
	 *
	 * @return string
	 */
	public function get_meta_type(): string {
		return 'term';
	}

	/**
	 * Getter for Object ID.
	 *
	 * @return int
	 */
	public function id(): int {
		return (int) $this->get( 'id' );
	}

	/**
	 * Getter for Object Name.
	 *
	 * @return string
	 */
	public function name(): string {
		return (string) $this->get( 'name' );
	}

	/**
	 * Getter for Object Slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return (string) $this->get( 'slug' );
	}

	/**
	 * Getter for Parent Object (if any)
	 *
	 * @return Core_Object|null
	 */
	public function parent(): ?Core_Object {
		if ( ! empty( $this->attributes['parent'] ) ) {
			return static::find( (int) $this->attributes['parent'] );
		}
	}

	/**
	 * Getter for Object Description
	 *
	 * @return string
	 */
	public function description(): string {
		return (string) $this->get( 'description' );
	}

	/**
	 * Getter for the Object Permalink
	 *
	 * @return string|null
	 */
	public function permalink(): ?string {
		$term_link = \get_term_link( $this->id() );
		return \is_wp_error( $term_link ) ? (string) $term_link : null;
	}
}
