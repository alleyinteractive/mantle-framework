<?php
namespace Mantle\Tests\Testkit;

use Mantle\Tests\Testkit\Concerns\Example_Overload;
use Mantle\Testkit\Test_Case;

class Test_Testkit_Test_Case extends Test_Case {
	use Example_Overload;

	public function test_create_application() {
		$this->assertInstanceOf( \Mantle\Contracts\Application::class, $this->create_application() );
	}

	public function test_make_request() {
		$this->get( '/' )->assertOk();
		$this->get( '/unknown/' )->assertNotFound();
	}

	public function test_url_generator() {
		$this->assertNotEmpty( $this->app['url'] );
		$this->assertEquals( home_url( '/example/' ), $this->app['url']->to( home_url( '/example/' ) ) );
		$this->assertEquals( home_url( '/example/' ), $this->app['url']->to( '/example/' ) );
	}

	/**
	 * Verifies that trait methods for {$trait}_setUpBeforeClass are called.
	 */
	public function test_trait_setup_process() {
		$this->assertNotEmpty( static::$overloaded_methods );
		$this->assertContains( 'setUpBeforeClass', static::$overloaded_methods );
	}
}
