<?php
namespace Mantle\Tests\Testing;

use WP_UnitTestCase;

class CoreTestShimTest extends WP_UnitTestCase {
	protected bool $setup_called = false;

	public function set_up(): void {
		parent::set_up();

		$this->setup_called = true;
	}

	public function test_go_to(): void {
		$this->go_to( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}

	public function test_core_set_up_called(): void {
		$this->assertTrue( $this->setup_called );
	}
}
