<?php
/**
 * This file contains the Test_Case class.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing;

use Mantle\Framework\Testing\Concerns\Deprecations;
use Mantle\Framework\Testing\Concerns\Hooks;
use Mantle\Framework\Testing\Concerns\Incorrect_Usage;
use Mantle\Framework\Testing\Concerns\Makes_Http_Requests;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Concerns\WordPress_State;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\TestCase as BaseTestCase;
use WP;
use WP_Query;
use function Mantle\Framework\Helpers\class_uses_recursive;

/**
 * Root Test Case for Mantle sites.
 */
abstract class Test_Case extends BaseTestCase {
	use Makes_Http_Requests,
		Deprecations,
		Incorrect_Usage,
		Hooks,
		WordPress_State;

	/**
	 * Array of traits that this class uses, with trait names as keys.
	 *
	 * @var array
	 */
	protected static $test_uses;

	/**
	 * Runs the routine before setting up all tests.
	 */
	public static function setUpBeforeClass(): void {
		static::register_traits();
		if ( isset( static::$test_uses[ Refresh_Database::class ] ) ) {
			static::refresh_database_pre_setup_before_class();
		}

		parent::setUpBeforeClass();

		if ( isset( static::$test_uses[ Refresh_Database::class ] ) ) {
			static::commit_transaction();
		}
	}

	/**
	 * Runs the routine after all tests have been run.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		if ( isset( static::$test_uses[ Refresh_Database::class ] ) ) {
			Utils::delete_all_data();
		}

		static::flush_cache();

		if ( isset( static::$test_uses[ Refresh_Database::class ] ) ) {
			static::commit_transaction();
		}
	}

	/**
	 * Runs the routine before each test is executed.
	 */
	protected function setUp(): void {
		set_time_limit( 0 );

		parent::setUp();

		$this->hooks_set_up();

		static::clean_up_global_scope();

		if ( isset( static::$test_uses[ Refresh_Database::class ] ) ) {
			$this->start_transaction();
		}

		$this->expectDeprecated();
		$this->expectIncorrectUsage();

		add_filter( 'wp_die_handler', [ WP_Die::class, 'get_handler' ] );
	}

	/**
	 * After a test method runs, reset any state in WordPress the test method might have changed.
	 */
	protected function tearDown(): void {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride,WordPress.NamingConventions.PrefixAllGlobals
		global $wp_query, $wp;

		if ( isset( static::$test_uses[ Refresh_Database::class ] ) ) {
			$this->refresh_database_tear_down();
		}

		if ( is_multisite() ) {
			while ( ms_is_switched() ) {
				restore_current_blog();
			}
		}
		$wp_query = new WP_Query();
		$wp       = new WP();

		// Reset globals related to the post loop and `setup_postdata()`.
		$post_globals = [
			'post',
			'id',
			'authordata',
			'currentday',
			'currentmonth',
			'page',
			'pages',
			'multipage',
			'more',
			'numpages',
		];
		foreach ( $post_globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		$this->unregister_all_meta_keys();
		remove_filter( 'wp_die_handler', [ WP_Die::class, 'get_handler' ] );
		$this->hooks_tear_down();
		wp_set_current_user( 0 );
		// phpcs:enable

		parent::tearDown();
	}

	/**
	 * Detect post-test failure conditions.
	 *
	 * We use this method to detect expectedDeprecated and expectedIncorrectUsage
	 * annotations.
	 */
	protected function assertPostConditions(): void {
		$this->expectedDeprecated();
		$this->expectedIncorrectUsage();
	}

	/**
	 * Register the traits that this test case uses.
	 */
	public static function register_traits() {
		static::$test_uses = array_flip( class_uses_recursive( static::class ) );
	}

	/**
	 * Asserts that the given value is an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertWPError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts that the given value is not an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertNotWPError( $actual, $message = '' ) {
		if ( '' === $message && is_wp_error( $actual ) ) {
			$message = $actual->get_error_message();
		}
		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts that the given fields are present in the given object.
	 *
	 * @param object $object The object to check.
	 * @param array  $fields The fields to check.
	 */
	public function assertEqualFields( $object, $fields ) {
		foreach ( $fields as $field_name => $field_value ) {
			if ( $object->$field_name !== $field_value ) {
				$this->fail();
			}
		}
	}

	/**
	 * Asserts that two values are equal, with whitespace differences discarded.
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public function assertDiscardWhitespace( $expected, $actual ) {
		$this->assertEquals( preg_replace( '/\s*/', '', $expected ), preg_replace( '/\s*/', '', $actual ) );
	}

	/**
	 * Asserts that two values are equal, with EOL differences discarded.
	 *
	 * @since 5.4.0
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public function assertEqualsIgnoreEOL( $expected, $actual ) {
		$this->assertEquals( str_replace( "\r\n", "\n", $expected ), str_replace( "\r\n", "\n", $actual ) );
	}

	/**
	 * Asserts that the contents of two un-keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @since 3.5.0
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public function assertEqualSets( $expected, $actual ) {
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the contents of two keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @since 4.1.0
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public function assertEqualSetsWithIndex( $expected, $actual ) {
		ksort( $expected );
		ksort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the given variable is a multidimensional array, and that all arrays are non-empty.
	 *
	 * @since 4.8.0
	 *
	 * @param array $array Array to check.
	 */
	public function assertNonEmptyMultidimensionalArray( $array ) {
		$this->assertTrue( is_array( $array ) );
		$this->assertNotEmpty( $array );

		foreach ( $array as $sub_array ) {
			$this->assertTrue( is_array( $sub_array ) );
			$this->assertNotEmpty( $sub_array );
		}
	}

	/**
	 * Checks each of the WP_Query is_* functions/properties against expected
	 * boolean value.
	 *
	 * Any properties that are listed by name as parameters will be expected to be
	 * true; all others are expected to be false. For example,
	 * assertQueryTrue( 'is_single', 'is_feed' ) means is_single() and is_feed()
	 * must be true and everything else must be false to pass.
	 *
	 * @param string ...$prop Any number of WP_Query properties that are expected
	 *                        to be true for the current request.
	 */
	public static function assertQueryTrue( ...$prop ) {
		global $wp_query;

		$all = [
			'is_404',
			'is_admin',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_comment_feed',
			'is_date',
			'is_day',
			'is_embed',
			'is_feed',
			'is_front_page',
			'is_home',
			'is_privacy_policy',
			'is_month',
			'is_page',
			'is_paged',
			'is_post_type_archive',
			'is_posts_page',
			'is_preview',
			'is_robots',
			'is_favicon',
			'is_search',
			'is_single',
			'is_singular',
			'is_tag',
			'is_tax',
			'is_time',
			'is_trackback',
			'is_year',
		];

		foreach ( $prop as $true_thing ) {
			PHPUnit::assertContains( $true_thing, $all, "Unknown conditional: {$true_thing}." );
		}

		$passed  = true;
		$message = '';

		foreach ( $all as $query_thing ) {
			$result = is_callable( $query_thing ) ? call_user_func( $query_thing ) : $wp_query->$query_thing;

			if ( in_array( $query_thing, $prop, true ) ) {
				if ( ! $result ) {
					$message .= $query_thing . ' is false but is expected to be true. ' . PHP_EOL;
					$passed   = false;
				}
			} elseif ( $result ) {
				$message .= $query_thing . ' is true but is expected to be false. ' . PHP_EOL;
				$passed   = false;
			}
		}

		if ( ! $passed ) {
			PHPUnit::fail( $message );
		}
	}
}
