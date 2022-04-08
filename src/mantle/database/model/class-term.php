<?php
/**
 * Term class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Query\Term_Query_Builder;
use Mantle\Support\Helpers;

/**
 * Term Model
 */
class Term extends Model implements Core_Object, Updatable {
	use Events\Term_Events,
		Meta\Model_Meta,
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
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		// Set the taxonomy on the model by default.
		$this->attributes['taxonomy'] = $this->get_object_name();

		parent::__construct( $object );
	}

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
	 * Query builder class to use.
	 *
	 * @return string|null
	 */
	public static function get_query_builder_class(): ?string {
		return Term_Query_Builder::class;
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
		return ! \is_wp_error( $term_link ) ? (string) $term_link : null;
	}

	/**
	 * Retrieve the core object for the underlying object.
	 *
	 * @return \WP_Term|null
	 */
	public function core_object(): ?\WP_Term {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_term_object( $id );
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
		$this->store_queued_meta();
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

	/**
	 * Get the registerable route for the model. By default this is set relative
	 * the object's archive route with the object's slug.
	 *
	 *     /object_name/object_slug/
	 *
	 * @return string|null
	 */
	public static function get_route(): ?string {
		$route_structure = static::get_archive_route() . '/{' . static::get_object_name() . '}/';

		/**
		 * Filter the route structure for a term handled through the entity router.
		 *
		 * @param string $route_structure Route structure.
		 * @param string $object_name Taxonomy name.
		 * @param string $object_class Model class name.
		 */
		return (string) apply_filters(
			'mantle_entity_router_term_route',
			$route_structure,
			static::get_object_name(),
			get_called_class()
		);
	}
}
