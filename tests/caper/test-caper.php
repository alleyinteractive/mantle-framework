<?php
/**
 * Class file for Caper_Test_Case
 *
 * @package Mantle
 */

namespace Mantle\Tests\Caper;

use Mantle\Caper\Caper;
use Mantle\Testing\Framework_Test_Case;

/**
 * Tests the Caper class.
 */
class Caper_Test_Case extends Framework_Test_Case {
	/**
	 * Test role name.
	 *
	 * @var string
	 */
	protected $role1 = 'bandit';

	/**
	 * Test role name.
	 *
	 * @var string
	 */
	protected $role2 = 'outlaw';

	/**
	 * A value known to not be the same as $data2, for comparisons.
	 *
	 * @var string
	 */
	protected $data1 = 'data1';

	/**
	 * A value known to not be the same as $data1, for comparisons.
	 *
	 * @var string
	 */
	protected $data2 = 'data2';

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		// These tests are not running in a VIP environment.
		\add_role( $this->role1, $this->role1, [] ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role
		\add_role( $this->role2, $this->role2, [] ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$this->reset_post_types();
		$this->reset_taxonomies();
	}

	/**
	 * Create and get a user with no roles or capabilities.
	 *
	 * @return \WP_User User instance.
	 */
	protected function get_no_roles_user() {
		$user = self::factory()->user->create_and_get();
		$user->remove_all_caps();
		return $user;
	}

	/**
	 * Test changing whether a primitive capability is possessed, then flipping
	 * it back to the original state.
	 */
	public function test_manipulating_primitives() {
		$user = $this->get_no_roles_user();

		$user->add_role( $this->role1 );

		Caper::grant_to( $this->role1 )->primitives( $this->data1 );

		$this->assertTrue( \user_can( $user, $this->data1 ) );

		Caper::deny_to( $this->role1 )->primitives( $this->data1 );

		$this->assertFalse( \user_can( $user, $this->data1 ) );
	}

	/**
	 * Test the "grant to all roles" feature by ensuring a user possesses the
	 * capability under every known role.
	 */
	public function test_granting_primitive_to_all() {
		Caper::grant_to_all()->primitives( $this->data1 );

		foreach ( \array_keys( \wp_roles()->get_names() ) as $role ) {
			$user = $this->get_no_roles_user();

			$user->add_role( $role );

			$this->assertTrue(
				\user_can( $user, $this->data1 ),
				\sprintf(
					'Failed to assert that a user with role %s possessed a primitive capability granted to all roles',
					$role
				)
			);
		}
	}

	/**
	 * Test the "deny to all roles" feature by ensuring a user lacks the
	 * capability under every known role.
	 */
	public function test_denying_primitive_to_all() {
		foreach ( \array_keys( \wp_roles()->get_names() ) as $name ) {
			$role = \get_role( $name );
			$role->add_cap( $this->data1 );
		}

		Caper::deny_to_all()->primitives( $this->data1 );

		foreach ( \array_keys( \wp_roles()->get_names() ) as $role ) {
			$user = $this->get_no_roles_user();

			$user->add_role( $role );

			$this->assertFalse(
				\user_can( $user, $this->data1 ),
				\sprintf(
					'Failed to assert that a user with role %s lacked a primitive capability denied to all roles',
					$role
				)
			);
		}

		// Clean up.
		foreach ( \array_keys( \wp_roles()->get_names() ) as $name ) {
			$role = \get_role( $name );
			$role->remove_cap( $this->data1 );
		}
	}

	/**
	 * The $caps_for argument for testing post type or taxonomy manipulation.
	 *
	 * One form tests the argument when provided as a string, and the other form
	 * tests the argument when provided as an array.
	 *
	 * @return array
	 */
	public function data_string_or_array_of_object_names() {
		return [
			[ $this->data1 ],
			[ [ $this->data1, $this->data2 ] ],
		];
	}

	/**
	 * Test changing whether post type capabilities are possessed, then
	 * flipping them back to their original state (with an exception).
	 *
	 * @dataProvider data_string_or_array_of_object_names
	 *
	 * @param string|array $string_or_array_of_post_types The post type or post types to pass to
	 *                                                    Caper to grant or deny.
	 */
	public function test_manipulating_post_type( $string_or_array_of_post_types ) {
		\register_post_type(
			$this->data1,
			[
				'capability_type' => $this->data1,
			]
		);

		\register_post_type(
			$this->data2,
			[
				'capability_type' => $this->data2,
			]
		);

		$user = $this->get_no_roles_user();

		$user->add_role( $this->role1 );

		Caper::grant_to( $this->role1 )->caps_for( $string_or_array_of_post_types );

		foreach ( (array) $string_or_array_of_post_types as $post_type ) {
			$pt_object = \get_post_type_object( $post_type );

			$this->assertTrue( \user_can( $user, $pt_object->cap->edit_posts ) );
		}

		Caper::deny_to( $this->role1 )->caps_for( $string_or_array_of_post_types );

		foreach ( (array) $string_or_array_of_post_types as $post_type ) {
			$pt_object = \get_post_type_object( $post_type );

			$this->assertFalse( \user_can( $user, $pt_object->cap->edit_posts ) );
		}

		Caper::grant_to( $this->role1 )->caps_for( $string_or_array_of_post_types )->except( 'edit_others_posts' );

		foreach ( (array) $string_or_array_of_post_types as $post_type ) {
			$pt_object = \get_post_type_object( $post_type );

			$this->assertTrue( \user_can( $user, $pt_object->cap->edit_posts ) );
			$this->assertFalse( \user_can( $user, $pt_object->cap->edit_others_posts ) );
		}

		Caper::grant_to( $this->role1 )->caps_for( $string_or_array_of_post_types )->only( 'edit_others_posts' );

		foreach ( (array) $string_or_array_of_post_types as $post_type ) {
			$pt_object = \get_post_type_object( $post_type );

			$this->assertTrue( \user_can( $user, $pt_object->cap->edit_others_posts ) );
			$this->assertFalse( \user_can( $user, $pt_object->cap->edit_posts ) );
		}
	}

	/**
	 * Test that the primitive, post type-independent capability 'read' is not distributed.
	 */
	public function test_read_capability_not_distributed() {
		$user = $this->get_no_roles_user();
		$user->add_role( $this->role1 );

		\register_post_type(
			$this->data1,
			[
				'capability_type' => $this->data1,
				'capabilities'    => [
					'read' => 'read',
				],
			]
		);

		$instance = Caper::grant_to( $this->role1 )->caps_for( $this->data1 );

		$this->assertArrayNotHasKey(
			'read',
			$instance->filter_user_has_cap( [], [], [], $user )
		);
	}

	/**
	 * Test that the 'read' capability for a post type is distributed when the
	 * capability is set to something other than 'read'.
	 */
	public function test_custom_read_capability_distributed() {
		$user = $this->get_no_roles_user();
		$user->add_role( $this->role1 );

		\register_post_type(
			$this->data1,
			[
				'capability_type' => $this->data1,
				'capabilities'    => [
					'read' => $this->data2,
				],
			]
		);

		$instance = Caper::grant_to( $this->role1 )->caps_for( $this->data1 );

		$this->assertArrayHasKey(
			$this->data2,
			$instance->filter_user_has_cap( [], [], [], $user )
		);
	}

	/**
	 * Test changing whether taxonomy capabilities are possessed, then flipping
	 * them back to their original state (with an exception).
	 *
	 * @dataProvider data_string_or_array_of_object_names
	 *
	 * @param string|array $string_or_array_of_taxonomies The taxonomy or taxonomies to
	 *                                                    pass to Caper to grant or deny.
	 */
	public function test_manipulating_taxonomy( $string_or_array_of_taxonomies ) {
		\register_taxonomy(
			$this->data1,
			$this->data2,
			[
				'capabilities' => [
					'manage_terms' => "manage_{$this->data1}",
					'edit_terms'   => "edit_{$this->data1}",
					'delete_terms' => "delete_{$this->data1}",
					'assign_terms' => "assign_{$this->data1}",
				],
			]
		);

		\register_taxonomy(
			$this->data2,
			$this->data1,
			[
				'capabilities' => [
					'manage_terms' => "manage_{$this->data2}",
					'edit_terms'   => "edit_{$this->data2}",
					'delete_terms' => "delete_{$this->data2}",
					'assign_terms' => "assign_{$this->data2}",
				],
			]
		);

		$user = $this->get_no_roles_user();

		$user->add_role( $this->role1 );

		Caper::grant_to( $this->role1 )->caps_for( $string_or_array_of_taxonomies );

		foreach ( (array) $string_or_array_of_taxonomies as $taxonomy ) {
			$taxonomy_object = \get_taxonomy( $taxonomy );

			$this->assertTrue( \user_can( $user, $taxonomy_object->cap->edit_terms ) );
		}

		Caper::deny_to( $this->role1 )->caps_for( $string_or_array_of_taxonomies );

		foreach ( (array) $string_or_array_of_taxonomies as $taxonomy ) {
			$taxonomy_object = \get_taxonomy( $taxonomy );

			$this->assertFalse( \user_can( $user, $taxonomy_object->cap->edit_terms ) );
		}

		Caper::grant_to( $this->role1 )->caps_for( $string_or_array_of_taxonomies )->except( 'delete_terms' );

		foreach ( (array) $string_or_array_of_taxonomies as $taxonomy ) {
			$taxonomy_object = \get_taxonomy( $taxonomy );

			$this->assertTrue( \user_can( $user, $taxonomy_object->cap->edit_terms ) );
			$this->assertFalse( \user_can( $user, $taxonomy_object->cap->delete_terms ) );
		}

		Caper::grant_to( $this->role1 )->caps_for( $string_or_array_of_taxonomies )->only( 'delete_terms' );

		foreach ( (array) $string_or_array_of_taxonomies as $taxonomy ) {
			$taxonomy_object = \get_taxonomy( $taxonomy );

			$this->assertTrue( \user_can( $user, $taxonomy_object->cap->delete_terms ) );
			$this->assertFalse( \user_can( $user, $taxonomy_object->cap->edit_terms ) );
		}
	}

	/**
	 * Test changing whether post type and taxonomy capabilities are possessed,
	 * then flipping them back to their original state (with an exception).
	 */
	public function test_manipulating_post_type_and_taxonomy() {
		\register_post_type(
			$this->data1,
			[
				'capability_type' => $this->data1,
			]
		);

		\register_taxonomy(
			$this->data2,
			$this->data1,
			[
				'capabilities' => [
					'manage_terms' => "manage_{$this->data2}",
					'edit_terms'   => "edit_{$this->data2}",
					'delete_terms' => "delete_{$this->data2}",
					'assign_terms' => "assign_{$this->data2}",
				],
			]
		);

		$user = $this->get_no_roles_user();

		$user->add_role( $this->role1 );

		$post_type_and_taxonomy = [ $this->data1, $this->data2 ];
		$pt_object              = \get_post_type_object( $this->data1 );
		$taxonomy_object        = \get_taxonomy( $this->data2 );

		Caper::grant_to( $this->role1 )->caps_for( $post_type_and_taxonomy );

		$this->assertTrue( \user_can( $user, $pt_object->cap->edit_posts ) );
		$this->assertTrue( \user_can( $user, $taxonomy_object->cap->edit_terms ) );

		Caper::deny_to( $this->role1 )->caps_for( $post_type_and_taxonomy );

		$this->assertFalse( \user_can( $user, $pt_object->cap->edit_posts ) );
		$this->assertFalse( \user_can( $user, $taxonomy_object->cap->edit_terms ) );

		Caper::grant_to( $this->role1 )
			->caps_for( $post_type_and_taxonomy )
			->except( [ 'edit_others_posts', 'delete_terms' ] );

		$this->assertTrue( \user_can( $user, $pt_object->cap->edit_posts ) );
		$this->assertFalse( \user_can( $user, $pt_object->cap->edit_others_posts ) );

		$this->assertTrue( \user_can( $user, $taxonomy_object->cap->edit_terms ) );
		$this->assertFalse( \user_can( $user, $taxonomy_object->cap->delete_terms ) );

		Caper::grant_to( $this->role1 )
			->only( [ 'edit_others_posts', 'delete_terms' ] )
			->caps_for( $post_type_and_taxonomy );

		$this->assertTrue( \user_can( $user, $pt_object->cap->edit_others_posts ) );
		$this->assertFalse( \user_can( $user, $pt_object->cap->edit_posts ) );

		$this->assertTrue( \user_can( $user, $taxonomy_object->cap->delete_terms ) );
		$this->assertFalse( \user_can( $user, $taxonomy_object->cap->edit_terms ) );
	}

	/**
	 * Test that any post type or taxonomy meta capabilities that are assigned
	 * when the object is registered are not assigned to users.
	 */
	public function test_meta_caps_are_not_granted() {
		\register_post_type(
			$this->data1,
			[
				'capability_type' => 'book',
			]
		);

		\register_taxonomy(
			$this->data2,
			$this->data1,
			[
				'capabilities' => [
					'manage_terms' => 'manage_genres',
					'edit_terms'   => 'edit_genres',
					'delete_terms' => 'delete_genres',
					'assign_terms' => 'assign_genres',
					// Here just to test that Caper doesn't assign it.
					'edit_term'    => 'edit_genre',
				],
			]
		);

		$user = $this->get_no_roles_user();

		$user->add_role( $this->role1 );

		$instance = Caper::grant_to( $this->role1 )->caps_for( [ $this->data1, $this->data2 ] );

		$actual = $instance->filter_user_has_cap( [], [], [], $user );

		$this->assertArrayNotHasKey( 'edit_book', $actual );
		$this->assertArrayNotHasKey( 'edit_genre', $actual );
	}

	/**
	 * Test chaining multiple Capers to first set a baseline distribution of
	 * capabilities for all roles, then modify select roles.
	 */
	public function test_granting_to_all_except() {
		$user1 = $this->get_no_roles_user();
		$user1->add_role( $this->role1 );

		$user2 = $this->get_no_roles_user();
		$user2->add_role( $this->role2 );

		$pt_object = \get_post_type_object( 'post' );

		Caper::grant_to_all()
			->caps_for( $pt_object->name )
			->then_deny_to( $this->role2 );

		$this->assertTrue( \user_can( $user1, $pt_object->cap->edit_posts ) );
		$this->assertFalse( \user_can( $user2, $pt_object->cap->edit_posts ) );
	}

	/**
	 * Test chaining multiple Capers to first set a baseline distribution of
	 * capabilities for all roles, then modify select roles.
	 */
	public function test_denying_to_all_except() {
		foreach ( [ $this->role1, $this->role2 ] as $name ) {
			$role = \get_role( $name );
			$role->add_cap( $this->data1 );
		}

		\get_role( $this->role1 )->add_cap( $this->data1 );
		\get_role( $this->role2 )->add_cap( $this->data1 );

		$user1 = $this->get_no_roles_user();
		$user1->add_role( $this->role1 );

		$user2 = $this->get_no_roles_user();
		$user2->add_role( $this->role2 );

		Caper::deny_to( $this->role1 )
			->primitives( $this->data1 )
			->then_grant_to( $this->role2 );

		$this->assertFalse( \user_can( $user1, $this->data1 ) );
		$this->assertTrue( \user_can( $user2, $this->data1 ) );

		// Clean up.
		foreach ( [ $this->role1, $this->role2 ] as $name ) {
			$role = \get_role( $name );
			$role->remove_cap( $this->data1 );
		}
	}

	/**
	 * Test that Caper can be invoked for a post type or taxonomy before it's registered.
	 */
	public function test_granting_capabilities_before_registration() {
		$user = $this->get_no_roles_user();
		$user->add_role( $this->role1 );

		$instance = Caper::grant_to( $this->role1 )->caps_for( [ $this->data1, $this->data2 ] );

		$this->assertEmpty( $instance->filter_user_has_cap( [], [], [], $user ) );

		\register_post_type(
			$this->data1,
			[
				'capability_type' => 'book',
			]
		);

		\register_taxonomy(
			$this->data2,
			$this->data1,
			[
				'capabilities' => [
					'manage_terms' => 'manage_genres',
				],
			]
		);

		$this->assertTrue( \user_can( $user->ID, \get_post_type_object( $this->data1 )->cap->edit_others_posts ) );
		$this->assertTrue( \user_can( $user->ID, \get_taxonomy( $this->data2 )->cap->manage_terms ) );
	}

	/**
	 * Test that the capabilities for a post type are removed from the map of
	 * granted capabilities after the post type is unregistered.
	 */
	public function test_capabilities_are_reset_after_post_type_unregistered() {
		$user1 = $this->get_no_roles_user();
		$user1->add_role( $this->role1 );

		\register_post_type(
			$this->data1,
			[
				'capability_type' => 'book',
			]
		);
		$pt_object = \get_post_type_object( $this->data1 );

		$instance = Caper::grant_to( $this->role1 )->caps_for( $this->data1 );

		$actual = $instance->filter_user_has_cap( [], [], [], $user1 );

		$this->assertArrayHasKey( 'edit_books', $actual );
		$this->assertTrue( \user_can( $user1, $pt_object->cap->edit_posts ) );

		\unregister_post_type( $this->data1 );

		$actual = $instance->filter_user_has_cap( [], [], [], $user1 );

		$this->assertArrayNotHasKey( 'edit_books', $actual );
	}

	/**
	 * Test that the capabilities for a taxonomy are removed from the map of
	 * granted capabilities after the taxonomy is unregistered.
	 */
	public function test_capabilities_are_reset_after_taxonomy_unregistered() {
		$user1 = $this->get_no_roles_user();
		$user1->add_role( $this->role1 );

		\register_post_type( $this->data1 );
		\register_taxonomy(
			$this->data2,
			$this->data1,
			[
				'capabilities' => [
					'edit_terms' => 'edit_genres',
				],
			]
		);
		$taxonomy = \get_taxonomy( $this->data2 );

		$instance = Caper::grant_to( $this->role1 )->caps_for( $this->data2 );

		$actual = $instance->filter_user_has_cap( [], [], [], $user1 );

		$this->assertArrayHasKey( 'edit_genres', $actual );
		$this->assertTrue( \user_can( $user1, $taxonomy->cap->edit_terms ) );

		\unregister_taxonomy( $this->data2 );

		$actual = $instance->filter_user_has_cap( [], [], [], $user1 );

		$this->assertArrayNotHasKey( 'edit_genres', $actual );
	}

	/**
	 * Test that the priority at which capabilities are distributed can be changed.
	 */
	public function test_changing_priorities() {
		$user1 = $this->get_no_roles_user();
		$user1->add_role( $this->role1 );

		$deny  = Caper::deny_to( $this->role1 )->primitives( $this->data1 );
		$grant = Caper::grant_to( $this->role1 )->primitives( $this->data1 );

		$deny->at_priority( 10 );
		$grant->at_priority( 10 );

		// Both filters have the same priority, so the newer one should win.
		$this->assertTrue( \user_can( $user1, $this->data1 ) );

		// The first one should win with the later priority.
		$deny->at_priority( 100 );
		$this->assertFalse( \user_can( $user1, $this->data1 ) );
	}

	/**
	 * Test the `users_roles_intersect()` utility function.
	 */
	public function test_user_roles_intersect() {
		$this->assertFalse( Caper::users_roles_intersect( $this->impossible_id, 'author' ) );

		$this->assertFalse( Caper::users_roles_intersect( new \WP_User(), 'editor' ) );

		$user = $this->get_no_roles_user();

		$this->assertFalse( Caper::users_roles_intersect( $user, $this->role1 ) );
		$this->assertFalse( Caper::users_roles_intersect( $user, [ $this->role1, $this->role2 ] ) );
		$this->assertFalse( Caper::users_roles_intersect( $user, $this->role2 ) );

		$user->add_role( $this->role1 );

		$this->assertTrue( Caper::users_roles_intersect( $user, $this->role1 ) );
		$this->assertTrue( Caper::users_roles_intersect( $user, [ $this->role1, $this->role2 ] ) );
		$this->assertFalse( Caper::users_roles_intersect( $user, $this->role2 ) );

		$user->add_role( $this->role2 );

		$this->assertTrue( Caper::users_roles_intersect( $user, $this->role1 ) );
		$this->assertTrue( Caper::users_roles_intersect( $user, [ $this->role1, $this->role2 ] ) );
		$this->assertTrue( Caper::users_roles_intersect( $user, $this->role2 ) );
	}
}
