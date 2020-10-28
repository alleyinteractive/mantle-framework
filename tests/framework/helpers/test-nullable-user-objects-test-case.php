<?php
/**
 * Class file for Nullable_User_Objects_Test_Case
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Helpers;

use Mantle\Framework\Testing\Framework_Test_Case;
use function Mantle\Framework\Helpers\get_user_object;
use function Mantle\Framework\Helpers\get_user_object_by;

/**
 * Unit tests for nullable user object functions.
 */
class Nullable_User_Objects_Test_Case extends Framework_Test_Case {
	/**
	 * Test that `get_user_object()` returns a user object.
	 */
	public function test_user_object_returns_user() {
		$known_id = self::factory()->user->create();

		$found = get_user_object( $known_id );

		$this->assertInstanceOf( \WP_User::class, $found );

		$this->assertSame( $known_id, $found->ID );
	}

	/**
	 * Test that `get_user_object()` returns null when there is no user.
	 */
	public function test_user_object_returns_null() {
		$found = get_user_object( $this->impossible_id );

		$this->assertNull( $found );
	}

	/**
	 * Test that `get_user_object_by()` returns a user object.
	 */
	public function test_user_object_by_returns_user() {
		$known_user = self::factory()->user->create_and_get();

		$found = get_user_object_by( 'email', $known_user->user_email );

		$this->assertInstanceOf( \WP_User::class, $found );

		$this->assertSame( $known_user->ID, $found->ID );
	}

	/**
	 * Test that `get_user_object_by()` returns null when there is no user.
	 */
	public function test_user_object_by_returns_null() {
		$found = get_user_object_by( 'email', \rand_str() );

		$this->assertNull( $found );
	}
}
