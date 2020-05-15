<?php
/**
 * Post class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use Mantle\Framework\Contracts;
use Mantle\Framework\Helpers;

/**
 * Post Model
 */
class Post extends Model implements Contracts\Database\Core_Object, Contracts\Database\Updatable {
	use Meta\Model_Meta,
		Meta\Post_Meta;

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
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'ID',
	];

	/**
	 * Post type for the model.
	 *
	 * @var string
	 */
	public static $object_name = 'post';

	/**
	 * Find a model by Object ID.
	 *
	 * @todo Add global scopes for the model to allow for the query builder
	 *       to verify the object type as well.
	 *
	 * @param \WP_Post|int $object Post to retrieve for.
	 * @return Post|null
	 */
	public static function find( $object ) {
		$post = Helpers\get_post_object( $object );

		if ( empty( $post ) ) {
			return null;
		}

		// Verify the object type matches the model type.
		$object_name = static::get_object_name();
		if ( $post->post_type !== $object_name ) {
			return null;
		}

		return static::new_from_existing( (array) $post );
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
	 * @return Contracts\Database\Core_Object|null
	 */
	public function parent(): ?Contracts\Database\Core_Object {
		if ( ! empty( $this->attributes['post_parent'] ) ) {
			return static::find( (int) $this->attributes['post_parent'] );
		}

		return null;
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

	/**
	 * Save the model.
	 *
	 * @param array $attributes Attributes to save.
	 * @return bool
	 *
	 * @throws Model_Exception Thrown on error saving.
	 */
	public function save( array $attributes = [] ) {
		$this->set_attributes( $attributes );

		$id = $this->id();

		if ( empty( $id ) ) {
			$save = \wp_insert_post( $this->get_attributes() );
		} else {
			$save = \wp_update_post(
				array_merge(
					$this->get_modified_attributes(),
					[
						'ID' => $id,
					]
				)
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		// Set the post ID attribute.
		$this->set_raw_attribute( 'ID', $save );

		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode.
	 * @return mixed
	 */
	public function delete( bool $force = false ) {
		return \wp_delete_post( $this->id(), $force );
	}
}
