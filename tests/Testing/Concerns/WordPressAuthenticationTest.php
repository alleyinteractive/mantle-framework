<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\Attributes\Acting_As;
use Mantle\Testing\Attributes\Acting_As_Super_Admin;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class WordPressAuthenticationTest extends FrameworkTestCase {
	private ?string $super_admin_login = null;
	private bool $super_admin_revoked = false;

	public function test_acting_as_role(): void {
		$user = $this->acting_as( 'administrator' );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_user(): void {
		$user = $this->acting_as( static::factory()->user->create_and_get( [ 'role' => 'administrator' ] ) );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_user_id(): void {
		$user = $this->acting_as( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_anonymous(): void {
		$this->assertNotAuthenticated();
		$this->assertGuest();
	}

	#[Acting_As( 'administrator' )]
	public function test_attribute_authentication(): void {
		$this->assertAuthenticated();
		$this->assertAuthenticated( 'administrator' );
	}

	#[Acting_As( 'subscriber' )]
	#[Acting_As_Super_Admin]
	public function test_super_admin_attribute(): void {
		$this->assertAuthenticated( 'administrator' );
		$this->assertTrue( is_super_admin( get_current_user_id() ) );
		$this->super_admin_login = wp_get_current_user()->user_login;

		$user_id = get_current_user_id();
		add_action( 'revoked_super_admin', function ( int $revoked_user_id ) use ( $user_id ): void {
			if ( $user_id === $revoked_user_id ) {
				$this->super_admin_revoked = true;
			}
		} );
	}

	/**
	 * Assert the super admin grant was cleaned up by the framework tear down.
	 *
	 * Uses `tearDown()` instead of `#[After( -10 )]`: the priority argument is
	 * not supported on PHPUnit 10.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		if ( null !== $this->super_admin_login ) {
			$this->assertTrue( $this->super_admin_revoked );
			$this->assertNotContains( $this->super_admin_login, get_super_admins() );
			$this->assertGuest();
		}
	}
}
