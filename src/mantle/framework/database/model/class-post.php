<?php
/**
 * Post class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use Mantle\Framework\Contracts\Database\Core_Object;
use Mantle\Framework\Database\Model\Meta\Model_Meta as Model_With_Meta;

/**
 * Post Model
 */
class Post extends Model_With_Meta implements Core_Object {
	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected static $aliases = [
		'content'     => 'post_content',
		'description' => 'post_excerpt',
		'id'          => 'ID',
		'name'        => 'post_title',
		'slug'        => 'post_name',
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
		$post = \get_post( $object_id );
		return $post ? new static( $post ) : null;
	}

	/**
	 * Get the meta type for the object.
	 *
	 * @return string
	 */
	public function get_meta_type(): string {
		return 'post';
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
		if ( ! empty( $this->attributes['post_parent'] ) ) {
			return static::find( (int) $this->attributes['post_parent'] );
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
	 * Getter for Object Status
	 *
	 * @return string
	 */
	public function status(): ?string {
		return $this->get( 'status' ) ?? null;
	}

	/**
	 * Getter for the Object Permalink
	 *
	 * @return string|null
	 */
	public function permalink(): ?string {
		return (string) \get_permalink( $this->id() );
	}
}
