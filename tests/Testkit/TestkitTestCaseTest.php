<?php
namespace Mantle\Tests\Testkit;

use Mantle\Tests\Testkit\Concerns\ExampleOverload;
use Mantle\Testkit\TestCase;

use function Mantle\Support\Helpers\with;

class TestkitTestCaseTest extends TestCase {
	use ExampleOverload;

	public function test_create_application(): void {
		$this->assertInstanceOf( \Mantle\Testkit\Application::class, with( $this->create_application() ) );
	}

	public function test_make_request(): void {
		$this->get( '/' )->assertOk();
		$this->get( '/unknown/' )->assertNotFound();
	}

	public function test_url_generator(): void {
		$this->assertNotNull( $this->app );

		$this->assertNotEmpty( $this->app['url'] );
		$this->assertSame( home_url( '/example/' ), $this->app['url']->to( home_url( '/example/' ) ) );
		$this->assertSame( home_url( '/example/' ), $this->app['url']->to( '/example/' ) );
	}

	/**
	 * Verifies that trait methods for {$trait}_setUpBeforeClass are called.
	 */
	public function test_trait_setup_process(): void {
		$this->assertNotEmpty( static::$overloaded_methods );
		$this->assertContains( 'setUpBeforeClass', static::$overloaded_methods );
	}

	public function test_router_not_registered(): void {
		$this->assertTrue( empty( $this->app['router'] ) );
	}
}
