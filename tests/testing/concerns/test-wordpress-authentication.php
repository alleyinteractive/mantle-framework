<?php
namespace Mantle\Testing\Concerns;

use Mantle\Console\Command;
use Mantle\Facade\Console;
use Mantle\Testing\Framework_Test_Case;

/**
 * @group testing
 */
class Test_WordPress_Authentication extends Framework_Test_Case {
	public function test_acting_as_role() {
		$user = $this->acting_as( 'administrator' );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_user() {
		$user = $this->acting_as( $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] ) );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_user_id() {
		$user = $this->acting_as( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertAuthenticated();
		$this->assertAuthenticated( $user );
		$this->assertAuthenticated( 'administrator' );
	}

	public function test_acting_as_anonymous() {
		$this->assertNotAuthenticated();
		$this->assertGuest();
	}
}
