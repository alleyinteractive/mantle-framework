<?php
namespace Mantle\Tests\Testing;

use Mantle\Support\Memoize;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\After;

if ( FrameworkTestCase::phpunit_version_compare( '11.0.0', '<' ) ) {
	class TestCaseTest extends FrameworkTestCase {
		/**
		 * Run before each test.
		 */
		protected function setUp(): void {
			parent::setUp();

			$this->markTestSkipped( 'These tests are for PHPUnit 11 and above.' );
		}

		public function test_placeholder(): void {
			$this->assertTrue( true );
		}
	}
} else {

	/**
	 * Tests for the core TestCase.
	 */
	class TestCaseTest extends FrameworkTestCase {
		/**
		 * Setup before the test class runs.
		 */
		public static function setUpBeforeClass(): void {
			parent::setUpBeforeClass();

			register_meta( 'post', 'testable_meta_key', [
				'single'       => true,
				'show_in_rest' => true,
				'type'         => 'string',
			] );
		}

		public function test_memoize_is_disabled(): void {
			$reflection = new \ReflectionClass( Memoize::class );
			$property   = $reflection->getProperty( 'enabled' );
			$this->assertFalse( $property->getValue() );
		}

		public function test_cleanup_globals_between_runs_first_run(): void {
			$GLOBALS['page'] = 2;

			$this->assertEquals( 2, $GLOBALS['page'] );

			putenv( 'WP_ENVIRONMENT_TYPE=testing' );
		}

		public function test_cleanup_globals_between_runs_second_run(): void {
			$this->assertNull( $GLOBALS['page'] ?? null );
			$this->assertEmpty( getenv( 'WP_ENVIRONMENT_TYPE' ) );
		}

		public function test_switch_to_site_restored(): void {
			$this->skipWithoutMultisite();

			$blog_id = static::factory()->blog->create();

			switch_to_blog( $blog_id );

			$this->assertEquals( $blog_id, get_current_blog_id() );
		}

		public function test_user_reset_after_test(): void {
			$this->actingAs( static::factory()->user->create() );

			$this->assertAuthenticated();
		}

		public function test_query_reset_after_test(): void {
			$post = static::factory()->post->create_and_get();
			$this->get( $post )->assertQueryTrue( 'is_single', 'is_singular' );

			$this->assertNotEmpty( $GLOBALS['wp_query']->get_queried_object() );
			$this->assertNotEmpty( $GLOBALS['wp_query']->query );
		}

		/**
		 * Assertions to run on tear down.
		 *
		 * The above tests attempt to break the global state. This method (which runs
		 * after the `tearDown()` method) will ensure it has been cleaned up properly.
		 *
		 * A priority of -10 is used to ensure this runs after the tearDown methods.
		 *
		 * @after
		 */
		#[After( -10 )]
		public function assertions_on_teardown(): void {
			if ( is_multisite() ) {
				$this->assertEquals( 1, get_current_blog_id() );
			}

			$this->assertGuest();

			$this->assertEmpty( $GLOBALS['wp_query']->get_queried_object() );
			$this->assertEmpty( $GLOBALS['wp_query']->query );

			$this->assertTrue( registered_meta_key_exists( 'post', 'testable_meta_key' ) );
		}
	}
}
