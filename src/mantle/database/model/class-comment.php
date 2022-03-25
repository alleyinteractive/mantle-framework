<?php
/**
 * Comment class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Contracts;
use Mantle\Support\Helpers;

/**
 * Comment Model
 */
class Comment extends Model implements Contracts\Database\Core_Object, Contracts\Database\Updatable {
	use Meta\Model_Meta,
		Meta\Comment_Meta;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected static $aliases = [
		'description' => 'comment_content',
		'id'          => 'comment_ID',
		'name'        => 'comment_author',
		'title'       => 'comment_author',
	];

	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'comment_ID',
	];

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		$this->attributes = (array) $object;
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param \WP_Comment|string|int $object Comment to retrieve.
	 * @return Comment|null
	 */
	public static function find( $object ) {
		$post = Helpers\get_comment_object( $object );
		return $post ? new static( $post ) : null;
	}

	/**
	 * Get the meta type for the object.
	 *
	 * @return string
	 */
	public function get_meta_type(): string {
		return 'comment';
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
		return (string) $this->get( 'name' );
	}

	/**
	 * Getter for Parent Object (if any)
	 *
	 * @return Contracts\Database\Core_Object|null
	 */
	public function parent(): ?Contracts\Database\Core_Object {
		$parent = $this->get_attribute( 'comment_parent' );

		if ( ! empty( $parent ) ) {
			return static::find( (int) $parent );
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
	 * Getter for the Object Permalink
	 *
	 * @return string|null
	 */
	public function permalink(): ?string {
		return (string) \get_comment_link( $this->id() );
	}

	/**
	 * Retrieve the core object for the underlying object.
	 *
	 * @return \WP_Comment|null
	 */
	public function core_object(): ?\WP_Comment {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_comment_object( $id );
		}

		return null;
	}

	/**
	 * Save the model.
	 *
	 * @param array $attributes Attributes to save.
	 * @throws Model_Exception Thrown on error saving.
	 */
	public function save( array $attributes = [] ) {
		$this->set_attributes( $attributes );

		$id = $this->id();

		if ( empty( $id ) ) {
			$save = \wp_insert_comment( $this->get_attributes() );
		} else {
			$save = \wp_update_comment(
				array_merge(
					$this->get_modified_attributes(),
					[
						'comment_ID' => $id,
					]
				)
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		$this->set_raw_attribute( 'comment_ID', $save );
		$this->store_queued_meta();
		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode.
	 */
	public function delete( bool $force = false ) {
		\wp_delete_comment( $this->id(), $force );
	}
}
