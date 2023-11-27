<?php

namespace Mantle\Tests\Framework;

use Mantle\Application\Application;
use Mantle\Facade\Facade;
use Mantle\Facade\Route;
use PHPUnit\Framework\TestCase;

/**
 * Designed to test the application in complete isolation for use outside of a
 * mantle-based application.
 */
class Test_Isolated_Framework extends TestCase {
	public Application $app;

	protected function setUp(): void {
		parent::setUp();

		$this->app = new Application();

		Application::set_instance( $this->app );

		if ( ! defined( 'WP_RUN_CORE_TESTS' ) ) {
			define( 'WP_RUN_CORE_TESTS', true );
		}
	}

	protected function tearDown(): void {
		parent::tearDown();

		Application::set_instance( null );

		unset( $this->app );
	}

	public function test_can_read_the_environment() {
		putenv( 'WP_ENVIRONMENT_TYPE=staging' );

		$this->assertEquals( 'staging', $this->app->environment() );
	}

	public function test_can_read_the_namespace() {
		$this->assertEquals( 'App', app()->get_namespace() );

		config()->set( 'app.namespace', 'MyApp' );
		$this->assertEquals( 'MyApp', app()->get_namespace() );
	}

	public function test_sets_up_facade() {
		$this->assertEquals( $this->app, Facade::get_facade_application() );
	}
}
