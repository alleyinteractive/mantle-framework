<?php
/**
 * This file contains the Test_Case class.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing;

use Mantle\Framework\Alias_Loader;
use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Facade\Facade;
use Mantle\Framework\Testing\Concerns\Admin_Screen;
use Mantle\Framework\Testing\Concerns\Assertions;
use Mantle\Framework\Testing\Concerns\Create_Application;
use Mantle\Framework\Testing\Concerns\Deprecations;
use Mantle\Framework\Testing\Concerns\Hooks;
use Mantle\Framework\Testing\Concerns\Incorrect_Usage;
use Mantle\Framework\Testing\Concerns\Interacts_With_Container;
use Mantle\Framework\Testing\Concerns\Interacts_With_Cron;
use Mantle\Framework\Testing\Concerns\Interacts_With_Requests;
use Mantle\Framework\Testing\Concerns\Makes_Http_Requests;
use Mantle\Framework\Testing\Concerns\Network_Admin_Screen;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Concerns\WordPress_Authentication;
use Mantle\Framework\Testing\Concerns\WordPress_State;
use Mantle\Framework\Testing\Factory\Factory_Container;
use PHPUnit\Framework\TestCase as BaseTestCase;
use WP;
use WP_Query;
use function Mantle\Framework\Helpers\class_basename;
use function Mantle\Framework\Helpers\class_uses_recursive;
use function Mantle\Framework\Helpers\collect;

/**
 * Root Test Case for Mantle sites.
 */
abstract class Test_Case extends BaseTestCase {
	use Assertions,
		Deprecations,
		Hooks,
		Incorrect_Usage,
		Interacts_With_Container,
		Interacts_With_Cron,
		Interacts_With_Requests,
		Makes_Http_Requests,
		WordPress_State,
		WordPress_Authentication;

	/**
	 * Array of traits that this class uses, with trait names as keys.
	 *
	 * @var array
	 */
	protected static $test_uses;

	/**
	 * Application instance.
	 *
	 * @var \Mantle\Framework\Application
	 */
	protected $app;

	/**
	 * Factory Instance.
	 *
	 * @var Factory_Container
	 */
	protected static $factory;

	/**
	 * Creates the application.
	 *
	 * @return \Mantle\Framework\Contracts\Application
	 */
	abstract public function create_application(): Application;

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

		if ( ! $this->app ) {
			$this->refresh_application();
		}

		// Clear the test factory.
		static::$factory = null;

		$this->hooks_set_up();

		static::clean_up_global_scope();

		foreach (
			[
				// This order is deliberate.
				Refresh_Database::class,
				WordPress_Authentication::class,
				Admin_Screen::class,
				Network_Admin_Screen::class,
				Interacts_With_Requests::class,
				Makes_Http_Requests::class,
			] as $trait
		) {
			if ( isset( static::$test_uses[ $trait ] ) ) {
				$method = strtolower( class_basename( $trait ) ) . '_set_up';
				$this->{$method}();
			}
		}

		$this->expectDeprecated();
		$this->expectIncorrectUsage();

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		add_filter( 'wp_die_handler', [ WP_Die::class, 'get_handler' ] );
	}

	/**
	 * After a test method runs, reset any state in WordPress the test method might have changed.
	 */
	protected function tearDown(): void {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride,WordPress.NamingConventions.PrefixAllGlobals
		global $wp_query, $wp;

		foreach (
			[
				// This order is deliberate.
				WordPress_Authentication::class,
				Admin_Screen::class,
				Network_Admin_Screen::class,
				Interacts_With_Requests::class,
				Refresh_Database::class,
			] as $trait
		) {
			if ( isset( static::$test_uses[ $trait ] ) ) {
				$method = strtolower( class_basename( $trait ) ) . '_tear_down';
				$this->{$method}();
			}
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

		// todo: Fire the shutdown hooks when added.
		if ( $this->app ) {
			$this->app = null;
		}
	}

	/**
	 * Register the traits that this test case uses.
	 */
	public static function register_traits() {
		static::$test_uses = array_flip( class_uses_recursive( static::class ) );
	}

	/**
	 * Refresh the application instance.
	 */
	protected function refresh_application() {
		$this->app = $this->create_application();

		Facade::set_facade_application( $this->app );
		Facade::clear_resolved_instances();

		Alias_Loader::set_instance( null );

		Model::set_event_dispatcher( $this->app['events'] );
	}

	/**
	 * Fetches the factory object for generating WordPress fixtures.
	 *
	 * @return \Mantle\Framework\Testing\Factory\Factory_Container
	 */
	public static function factory() {
		if ( ! isset( static::$factory ) ) {
			static::$factory = new Factory_Container( app() );
		}

		return static::$factory;
	}
}
