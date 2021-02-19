<?php
/**
 * Caper class file
 *
 * @package Mantle
 */

namespace Mantle\Caper;

/**
 * Fluently distribute capabilities to roles.
 */
class Caper {
	/**
	 * The roles to which this instance grants or denies capabilities.
	 *
	 * @var array
	 */
	private $positive_roles = [];

	/**
	 * Whether capabilities are being granted or denied.
	 *
	 * @var bool
	 */
	private $allow;

	/**
	 * Primitive capabilities to distribute directly.
	 *
	 * @var array
	 */
	private $primitives = [];

	/**
	 * Post types whose capabilities should be distributed.
	 *
	 * @var array
	 */
	private $post_types = [];

	/**
	 * Taxonomies whose capabilities should be distributed.
	 *
	 * @var array
	 */
	private $taxonomies = [];

	/**
	 * For post types or taxonomies, generic primitive capabilities
	 * to grant instead of deny, or vice versa.
	 *
	 * @var array
	 */
	private $exceptions = [];

	/**
	 * For post types or taxonomies, the exclusive set of generic primitive
	 * capabilities to grant or deny.
	 *
	 * @var array
	 */
	private $only = [];

	/**
	 * The calculated associative array of capabilities to be granted or denied.
	 *
	 * @var array
	 */
	private $map;

	/**
	 * Priority at which user capabilities are filtered.
	 *
	 * @var int
	 */
	private $priority;

	/**
	 * A special array used by this class to stand for "all roles."
	 *
	 * @var array
	 */
	private const ALL_ROLES = [ '__ALL__' ];

	/**
	 * Meta capabilities for taxonomy terms.
	 *
	 * @var array
	 */
	private const TAXONOMY_META_CAPABILITIES = [ 'edit_term', 'delete_term', 'assign_term' ];

	/**
	 * Set up.
	 *
	 * @param array $positive_roles The roles to which this instance grants or denies capabilities.
	 * @param bool  $allow          Whether capabilities are being granted or denied.
	 * @param int   $priority       Priority at which to filter user capabilities.
	 */
	private function __construct( array $positive_roles, bool $allow, int $priority ) {
		$this->positive_roles = $positive_roles;
		$this->allow          = $allow;
		$this->priority       = $priority;

		$this->at_priority( $this->priority );

		\add_action( 'registered_post_type', [ $this, 'reset_map' ] );
		\add_action( 'unregistered_post_type', [ $this, 'reset_map' ] );
		\add_action( 'registered_taxonomy', [ $this, 'reset_map' ] );
		\add_action( 'unregistered_taxonomy', [ $this, 'reset_map' ] );
	}

	/**
	 * Start a Caper that grants capabilities to roles.
	 *
	 * @param string|array $positive_roles The roles to affect.
	 * @return static Class instance.
	 */
	public static function grant_to( $positive_roles ) {
		return new static( (array) $positive_roles, true, 10 );
	}

	/**
	 * Start a Caper that grants capabilities to all roles.
	 *
	 * @return static Class instance.
	 */
	public static function grant_to_all() {
		return new static( static::ALL_ROLES, true, 10 );
	}

	/**
	 * Start a Caper that denies capabilities to roles.
	 *
	 * @param string|array $positive_roles The roles to affect.
	 * @return static Class instance.
	 */
	public static function deny_to( $positive_roles ) {
		return new static( (array) $positive_roles, false, 10 );
	}

	/**
	 * Start a Caper that denies capabilities to all roles.
	 *
	 * @return static Class instance.
	 */
	public static function deny_to_all() {
		return new static( static::ALL_ROLES, false, 10 );
	}

	/**
	 * Set primitive capabilities to grant or deny.
	 *
	 * @param string|array $primitives Array of primitive capabilities.
	 * @return static Class instance.
	 */
	public function primitives( $primitives ) {
		$this->primitives = \array_merge( $this->primitives, (array) $primitives );
		$this->primitives = \array_unique( $this->primitives );
		return $this;
	}

	/**
	 * Set the post type or taxonomy whose capabilities will be granted or denied.
	 *
	 * A post type and a taxonomy will almost never share a name, making it
	 * redundant to specify "for post type" or "for taxonomy" and cheap to
	 * determine which object type the name corresponds to. Should a post type
	 * and taxonomy share a name, use the Caper::caps_for_post_type() or
	 * Caper::caps_for_taxonomy() methods directly to disambiguate.
	 *
	 * @param string|array $type Post type or taxonomy names.
	 * @return static Class instance.
	 */
	public function caps_for( $type ) {
		return $this
			->caps_for_post_type( $type )
			->caps_for_taxonomy( $type );
	}

	/**
	 * Set the post types whose capabilities will be granted or denied.
	 *
	 * @param string|array $type Post type or types.
	 * @return static Class instance.
	 */
	public function caps_for_post_type( $type ) {
		$this->post_types = \array_merge( $this->post_types, (array) $type );
		$this->post_types = \array_unique( $this->post_types );
		return $this;
	}

	/**
	 * Set the taxonomies whose capabilities will be granted or denied.
	 *
	 * @param string|array $type Taxonomy or taxonomies.
	 * @return static Class instance.
	 */
	public function caps_for_taxonomy( $type ) {
		$this->taxonomies = \array_merge( $this->taxonomies, (array) $type );
		$this->taxonomies = \array_unique( $this->taxonomies );
		return $this;
	}

	/**
	 * Set exceptions to the granted or denied post type or taxonomy capabilities.
	 *
	 * The $primitives parameter refers to the "generic" keys in the $cap object
	 * of a \WP_Post_Type or \WP_Taxonomy that correspond to the actual
	 * capability names.
	 *
	 * For example, given a post type with a 'capability_type' of 'book', pass
	 * this method 'edit_published_posts', not 'edit_published_books'. The
	 * actual capabilities to grant or deny will be determined automatically.
	 *
	 * @param string|array $primitives Generic capability names to grant instead
	 *                                 of deny, or vice versa, depending on the
	 *                                 value of $allow.
	 * @return static Class instance.
	 */
	public function except( $primitives ) {
		$this->exceptions = (array) $primitives;
		return $this;
	}

	/**
	 * Set the post type or taxonomy capabilities to exclusively grant or deny.
	 *
	 * The $primitives parameter refers to the "generic" keys in the $cap object
	 * of a \WP_Post_Type or \WP_Taxonomy that correspond to the actual
	 * capability names.
	 *
	 * For example, given a post type with a 'capability_type' of 'book', pass
	 * this method 'edit_published_posts', not 'edit_published_books'. The
	 * actual capabilities to grant or deny will be determined automatically.
	 *
	 * @param string|array $primitives Generic capability names to grant or deny.
	 * @return static Class instance.
	 */
	public function only( $primitives ) {
		$this->only = (array) $primitives;
		return $this;
	}

	/**
	 * Change the priority at which user capabilities are filtered.
	 *
	 * @param int $priority New priority.
	 * @return static Class instance.
	 */
	public function at_priority( int $priority ) {
		\remove_filter( 'user_has_cap', [ $this, 'filter_user_has_cap' ], $this->priority );
		$this->priority = $priority;
		\add_filter( 'user_has_cap', [ $this, 'filter_user_has_cap' ], $this->priority, 4 );
		return $this;
	}

	/**
	 * Convenience method for chaining multiple related Caper instances.
	 *
	 * For example:
	 *
	 *     Caper::deny_to_all()
	 *         ->caps_for( 'post' )
	 *         ->then_grant_to( 'editor' )
	 *         ->except( 'delete_posts' )
	 *         ->then_grant_to( 'administrator' );
	 *
	 * @param string|array $positive_roles The roles to affect.
	 * @return static New class instance.
	 */
	public function then_grant_to( $positive_roles ) {
		$next = static::grant_to( $positive_roles );
		$this->copy_settings_into( $next );
		return $next;
	}

	/**
	 * Convenience method for chaining multiple related Caper instances.
	 *
	 * For example:
	 *
	 *     Caper::grant_to_all()
	 *         ->caps_for( 'post' )
	 *         ->then_deny_to( [ 'subscriber', 'contributor' ] );
	 *
	 * @param string|array $positive_roles The roles to affect.
	 * @return static New class instance.
	 */
	public function then_deny_to( $positive_roles ) {
		$next = static::deny_to( $positive_roles );
		$this->copy_settings_into( $next );
		return $next;
	}

	/**
	 * Dynamically filter a user's capabilities.
	 *
	 * @param array    $allcaps An array of all the user's capabilities.
	 * @param array    $caps    Actual capabilities for meta capability.
	 * @param array    $args    Optional parameters passed to has_cap(), typically object ID.
	 * @param \WP_User $user    The user object.
	 * @return array The updated array of the user's capabilities.
	 */
	public function filter_user_has_cap( $allcaps, $caps, $args, $user ) {
		unset( $caps, $args );

		if (
			( static::ALL_ROLES === $this->positive_roles && \count( $user->roles ) > 0 )
			|| static::users_roles_intersect( $user, $this->positive_roles )
		) {
			$allcaps = \array_merge( $allcaps, $this->get_map() );
		}

		return $allcaps;
	}

	/**
	 * Force the map of capabilities be recalculated the next time it's needed.
	 */
	public function reset_map() {
		$this->map = null;
	}

	/**
	 * Whether a particular user has a specific role or roles.
	 *
	 * @param int|\WP_User $user User ID or object.
	 * @param string|array $roles Role name or names to check.
	 * @return bool Whether the user has any of the given roles.
	 */
	public static function users_roles_intersect( $user, $roles ): bool {
		if ( ! ( $user instanceof \WP_User ) ) {
			$user = \get_userdata( $user );
		}

		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		if ( ! \is_array( $roles ) ) {
			$roles = [ $roles ];
		}

		$comparison = \array_intersect( $roles, (array) $user->roles );

		return ( \count( $comparison ) > 0 );
	}

	/**
	 * Get the associative array of capabilities to be granted or denied.
	 *
	 * @return array Array of capabilities and their status.
	 */
	protected function get_map() {
		if ( \is_array( $this->map ) ) {
			return $this->map;
		}

		$this->map = [];

		if ( $this->primitives ) {
			$this->map = \array_merge( $this->map, \array_fill_keys( $this->primitives, $this->allow ) );
		}

		foreach ( $this->post_types as $post_type ) {
			if ( \post_type_exists( $post_type ) ) {
				$post_type_primitives_map = $this->get_post_type_primitives_map( \get_post_type_object( $post_type ) );

				$this->map = \array_merge( $this->map, $this->map_primitives_array( $post_type_primitives_map ) );
			}
		}

		foreach ( $this->taxonomies as $taxonomy ) {
			if ( \taxonomy_exists( $taxonomy ) ) {
				$taxonomy_primitives_map = $this->get_taxonomy_primitives_map( \get_taxonomy( $taxonomy ) );

				$this->map = \array_merge( $this->map, $this->map_primitives_array( $taxonomy_primitives_map ) );
			}
		}

		return $this->map;
	}

	/**
	 * Get the map of generic to actual primitive capabilities for a post type.
	 *
	 * @param \WP_Post_Type $post_type Post type object.
	 * @return array The map of capabilities cast as an array.
	 */
	protected function get_post_type_primitives_map( \WP_Post_Type $post_type ) {
		global $post_type_meta_caps;

		/*
		 * Pretty close to guaranteed that each known meta cap will be in the array
		 * values as long as one post type is registered with 'map_meta_cap'.
		 */
		$meta_caps = \array_unique( \array_values( $post_type_meta_caps ?? [] ) );

		$cap = \get_object_vars( $post_type->cap );

		foreach ( \array_keys( $cap ) as $core_cap ) {
			if ( \in_array( $core_cap, $meta_caps, true ) ) {
				unset( $cap[ $core_cap ] );
			}
		}

		/*
		 * If the $read cap is set to 'read', which it is for all post types by
		 * default, then don't disrupt it. But if $read was configured in the
		 * 'capabilities' argument to `register_post_type()`, include it to be
		 * granted or denied.
		 */
		if ( isset( $cap['read'] ) && 'read' === $cap['read'] ) {
			unset( $cap['read'] );
		}

		return $cap;
	}

	/**
	 * Get the primitive capability keys and names for a taxonomy.
	 *
	 * @param \WP_Taxonomy $taxonomy Taxonomy to use.
	 * @return array Associative array of primitive taxonomy capability keys and their values for the taxonomy.
	 */
	protected function get_taxonomy_primitives_map( \WP_Taxonomy $taxonomy ) {
		$cap = \get_object_vars( (object) $taxonomy->cap );

		foreach ( \array_keys( $cap ) as $core_cap ) {
			if ( \in_array( $core_cap, self::TAXONOMY_META_CAPABILITIES, true ) ) {
				unset( $cap[ $core_cap ] );
			}
		}

		return $cap;
	}

	/**
	 * Get the associative array of an object's capabilities to grant or deny.
	 *
	 * @param array $map Map of post type or taxonomy capabilities.
	 * @return array Array of capabilities and their status.
	 */
	protected function map_primitives_array( array $map ) {
		$result = \array_fill_keys( \array_values( $map ), $this->allow );

		if ( $this->only ) {
			foreach ( \array_keys( $map ) as $primitive ) {
				if ( \in_array( $primitive, $this->only, true ) ) {
					continue;
				}

				$result[ $map[ $primitive ] ] = ! $this->allow;
			}
		}

		foreach ( $this->exceptions as $exception ) {
			if ( isset( $map[ $exception ] ) ) {
				$result[ $map[ $exception ] ] = ! $this->allow;
			}
		}

		return $result;
	}

	/**
	 * Copy this instance's settings into another class instance.
	 *
	 * @param self $instance Other class instance.
	 */
	private function copy_settings_into( self $instance ) {
		if ( $this->primitives ) {
			$instance->primitives( $this->primitives );
		}

		if ( $this->post_types ) {
			$instance->caps_for_post_type( $this->post_types );
		}

		if ( $this->taxonomies ) {
			$instance->caps_for_taxonomy( $this->taxonomies );
		}

		$instance->at_priority( $this->priority );
	}
}
