<?php
namespace Mantle\Tests\Concerns;

use Mantle\Testing\Attributes\Acting_As;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group testing
 */
#[Group( 'testing' )]
class WordPressAuthenticationTest extends FrameworkTestCase {
	public function test_acting_as_role(): void {
		$user = $this->acting_as( 'administrator' );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_user(): void {
		$user = $this->acting_as( $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] ) );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_user_id(): void {
		$user = $this->acting_as( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

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
}
