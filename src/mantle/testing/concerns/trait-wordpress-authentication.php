<?php
/**
 * This file contains the WordPress_Authentication trait
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Model\User;
use Mantle\Testing\Exceptions\Exception;
use WP_User;
use function Mantle\Support\Helpers\get_user_object;

/**
 * Trait to provide authentication-related testing functionality.
 */
trait WordPress_Authentication {
	/**
	 * Backed up global user ID.
	 *
	 * @var int
	 */
	protected $backup_user;

	/**
	 * Backup the current global user.
	 */
	public function wordpress_authentication_set_up() {
		$this->backup_user = get_current_user_id();
	}

	/**
	 * Restore the backed up global user.
	 */
	public function wordpress_authentication_tear_down() {
		// If the user changed, set it back.
		if ( get_current_user_id() !== $this->backup_user ) {
			wp_set_current_user( $this->backup_user );
		}
	}

	/**
	 * Set the current user.
	 *
	 * If a \WP_User, int (user ID), or Mantle\Database\Model\User is
	 * passed, that user will be used. If a string is passed, that is assumed to
	 * be a role, and a new user will be created with that role. The user (as a
	 * \WP_User) will then be returned.
	 *
	 * @throws Exception|Model_Exception If the user could not be set or created.
	 *
	 * @param User|WP_User|string|int|null $user Either a user to use, or a role in which to create a new
	 *                    user.
	 * @return \WP_User User which is now being used in the WordPress state.
	 */
	public function acting_as( User|WP_User|string|int|null $user ): WP_User {
		$user_id = null;
		if ( is_string( $user ) ) {
			$model   = $this->create_user_with_role( $user );
			$user_id = $model->id();
		} elseif ( is_int( $user ) ) {
			$user_id = $user;
		} elseif ( $user instanceof User ) {
			$user_id = $user->id();
		} elseif ( $user instanceof WP_User ) {
			$user_id = $user->ID;
		}

		if ( $user_id ) {
			wp_set_current_user( $user_id );
			return get_user_object( $user_id );
		}

		throw new Exception( 'Could not find or create the user' );
	}

	/**
	 * Alias to `acting_as()`.
	 *
	 * @param User|WP_User|string|int|null $user
	 */
	public function actingAs( User|WP_User|string|int|null $user ): WP_User {
		return $this->acting_as( $user );
	}

	/**
	 * Generate a user with the given role.
	 *
	 * @throws Model_Exception If the user could not be saved to the database.
	 *
	 * @param string $role Role.
	 * @return User
	 */
	public function create_user_with_role( string $role ): User {
		static $sequence = 1;

		$model = new User(
			[
				'role'       => $role,
				'user_login' => "User {$sequence}",
				'user_pass'  => 'password',
				'user_email' => "user_{$sequence}@example.org",
			]
		);
		$model->save();
		$sequence++;
		return $model;
	}

	/**
	 * Assert that we are authenticated with a given user/role.
	 *
	 * @param User|WP_User|string|int|null $user User to check.
	 */
	public function assertAuthenticated( User|WP_User|string|int|null $user = null ): void {
		if ( is_null( $user ) ) {
			$this->assertTrue( is_user_logged_in(), 'User is not authenticated.' );
		}

		$user = wp_get_current_user();

		if ( empty( $user ) ) {
			$this->fail( 'User is not authenticated.' );
		}

		match ( true ) {
			$user instanceof User => $this->assertEquals( $user->id(), get_current_user_id() ),
			is_int( $user ) => $this->assertEquals( $user, get_current_user_id() ),
			$user instanceof WP_User => $this->assertEquals( $user->ID, get_current_user_id() ),
			is_string( $user ) => $this->assertTrue( in_array( $user, $user->roles, true ) ),
		};
	}

	/**
	 * Assert that we are not authenticated.
	 */
	public function assertGuest(): void {
		$this->assertFalse( is_user_logged_in(), 'User is authenticated.' );
	}

	/**
	 * Assert that the given user not authenticated.
	 *
	 * Alias to `assertGuest()`.
	 */
	public function assertNotAuthenticated(): void {
		$this->assertGuest();
	}
}
