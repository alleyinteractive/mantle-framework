<?php
/**
 * Term class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use Mantle\Framework\Contracts\Database\Core_Object;
use Mantle\Framework\Contracts\Database\Updatable;
use Mantle\Framework\Helpers;

/**
 * Term Model
 */
class Term extends Model implements Core_Object, Updatable {
	use Meta\Model_Meta,
		Meta\Term_Meta;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected static $aliases = [
		'id' => 'term_id',
	];

	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'term_id',
	];

	/**
	 * Taxonomy of the term.
	 *
	 * @return string
	 */
	public function taxonomy(): string {
		return $this->get( 'taxonomy' );
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param \WP_Term|string|int $object Term to retrieve.
	 * @return Term|null
	 */
	public static function find( $object ) {
		$term = Helpers\get_term_object( $object );

		if ( empty( $term ) ) {
			return null;
		}

		// Bail if the taxonomy doesn't match the expected object type.
		if ( static::get_object_name() !== $term->taxonomy ) {
			return null;
		}

		return static::new_from_existing( (array) $term );
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
		$parent = $this->get( 'parent' );

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
		$term_link = \get_term_link( $this->id() );
		return \is_wp_error( $term_link ) ? (string) $term_link : null;
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
			$save = \wp_insert_term(
				$this->name(),
				$this->taxonomy(),
				$this->get_attributes()
			);
		} else {
			$save = \wp_update_term(
				$this->id(),
				$this->taxonomy(),
				$this->get_modified_attributes()
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		$this->set_raw_attribute( 'term_id', $save['term_id'] );

		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the model.
	 *
	 * @param bool $force Force delete the mode, not used.
	 */
	public function delete( bool $force = false ) {
		\wp_delete_term( $this->id(), $this->taxonomy() );
	}
}
