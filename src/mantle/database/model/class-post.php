<?php
/**
 * Post class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Carbon\Carbon;
use DateTime;
use Mantle\Contracts;
use Mantle\Database\Query\Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Support\Helpers;

use function Mantle\Support\Helpers\collect;

/**
 * Post Model
 */
class Post extends Model implements Contracts\Database\Core_Object, Contracts\Database\Updatable {
	use Events\Post_Events,
		Meta\Model_Meta,
		Meta\Post_Meta,
		Term\Model_Term;

	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected static $aliases = [
		'content'     => 'post_content',
		'date'        => 'post_date',
		'description' => 'post_excerpt',
		'id'          => 'ID',
		'title'       => 'post_title',
		'name'        => 'post_title',
		'slug'        => 'post_name',
		'status'      => 'post_status',
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
	 * The attributes that should be hidden for serialization.
	 *
	 * @var string[]
	 */
	protected $hidden = [
		'post_password',
	];

	/**
	 * Post type for the model.
	 *
	 * @var string
	 */
	public static $object_name;

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object = [] ) {
		// Set the post type on the model by default.
		$this->attributes['post_type'] = $this->get_object_name();

		parent::__construct( $object );
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @todo Add global scopes for the model to allow for the query builder
	 *       to verify the object type as well.
	 *
	 * @param \WP_Post|int $object Post to retrieve for.
	 * @return static|null
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
	 * Query builder class to use.
	 *
	 * @return string|null
	 */
	public static function get_query_builder_class(): ?string {
		return Post_Query_Builder::class;
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
	 * Retrieve the core object for the underlying object.
	 *
	 * @return \WP_Post|null
	 */
	public function core_object(): ?\WP_Post {
		$id = $this->id();

		if ( $id ) {
			return Helpers\get_post_object( $id );
		}

		return null;
	}

	/**
	 * Publish a post.
	 */
	public function publish() : bool {
		return $this->save( [ 'status' => 'publish' ] );
	}

	/**
	 * Schedule a post for publication.
	 *
	 * @param string|DateTime $date Date to schedule the post for.
	 * @return bool
	 */
	public function schedule( $date ) : bool {
		if ( $date instanceof DateTime ) {
			$date = $date->format( 'Y-m-d H:i:s' );
		} else {
			$date = Carbon::parse( $date )->format( 'Y-m-d H:i:s' );
		}

		return $this->save(
			[
				'status' => 'future',
				'date'   => $date,
			] 
		);
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
			$save = \wp_insert_post( $this->get_attributes(), true );
		} else {
			$save = \wp_update_post(
				array_merge(
					$this->get_modified_attributes(),
					[
						'ID' => $id,
					]
				),
				true
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		// Set the post ID attribute.
		$this->set_raw_attribute( 'ID', $save );

		$this->store_queued_meta();
		$this->store_queued_terms();
		$this->refresh();
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

	/**
	 * Allow the query to query against any post status.
	 *
	 * This will check against _any_ post status that is registered and does not
	 * use the 'any' post_status attribute.
	 *
	 * @param Builder $builder Query builder instance.
	 * @return Builder
	 */
	public function scopeAnyStatus( Builder $builder ): Builder {
		return $builder->where(
			'post_status',
			array_values( get_post_stati( [], 'names' ) )
		);
	}

	/**
	 * Get the registerable route for the model.
	 *
	 * @return string|null
	 */
	public static function get_route(): ?string {
		if ( 'post' === static::get_object_name() ) {
			$structure = get_option( 'permalink_structure' );

			if ( ! empty( $structure ) ) {
				$index     = 1;
				$structure = preg_replace_callback(
					'/\%/',
					function () use ( &$index ) {
						return ( $index++ ) % 2 ? '{' : '}';
					},
					$structure
				);

				$route_structure = str_replace( '{postname}', '{post}', $structure );
			} else {
				$route_structure = null;
			}
		} else {
			$route_structure = '/' . static::get_object_name() . '/{slug}';
		}

		/**
		 * Filter the route structure for a post handled through the entity router.
		 *
		 * @param string $route_structure Route structure.
		 * @param string $object_name Post type.
		 * @param string $object_class Model class name.
		 */
		return (string) apply_filters(
			'mantle_entity_router_post_route',
			$route_structure,
			static::get_object_name(),
			get_called_class()
		);
	}
}
