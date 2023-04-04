<?php
/**
 * This file contains the Test_Case class.
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Container\Container;
use Mantle\Contracts\Application;
use Mantle\Database\Model\Model;
use Mantle\Facade\Facade;
use Mantle\Framework\Alias_Loader;
use Mantle\Support\Collection;
use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testing\Concerns\Assertions;
use Mantle\Testing\Concerns\Core_Shim;
use Mantle\Testing\Concerns\Deprecations;
use Mantle\Testing\Concerns\Hooks;
use Mantle\Testing\Concerns\Incorrect_Usage;
use Mantle\Testing\Concerns\Interacts_With_Console;
use Mantle\Testing\Concerns\Interacts_With_Container;
use Mantle\Testing\Concerns\Interacts_With_Cron;
use Mantle\Testing\Concerns\Interacts_With_Hooks;
use Mantle\Testing\Concerns\Interacts_With_Requests;
use Mantle\Testing\Concerns\Makes_Http_Requests;
use Mantle\Testing\Concerns\Network_Admin_Screen;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Concerns\WordPress_Authentication;
use Mantle\Testing\Concerns\WordPress_State;
use Mantle\Testing\Factory\Factory_Container;
use PHPUnit\Framework\TestCase as BaseTestCase;
use WP;
use WP_Query;
use function Mantle\Support\Helpers\class_basename;
use function Mantle\Support\Helpers\class_uses_recursive;
use function Mantle\Support\Helpers\collect;

/**
 * Root Test Case for Mantle sites.
 */
abstract class Test_Case extends BaseTestCase {
	use Assertions,
		Core_Shim,
		Deprecations,
		Hooks,
		Incorrect_Usage,
		Interacts_With_Console,
		Interacts_With_Container,
		Interacts_With_Cron,
		Interacts_With_Hooks,
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
	 * @var \Mantle\Contracts\Container|\Mantle\Container\Container
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
	 * @return \Mantle\Contracts\Application
	 */
	abstract public function create_application(): Application;

	/**
	 * Runs the routine before setting up all tests.
	 */
	public static function setUpBeforeClass(): void {
		static::register_traits();

		if ( ! empty( static::$test_uses ) ) {

			static::get_test_case_traits()
				->each(
					function( $trait ) {
						$method = strtolower( class_basename( $trait ) ) . '_set_up_before_class';

						if ( method_exists( static::class, $method ) ) {
							call_user_func( [ static::class, $method ] );
						}
					}
				);
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

		// Set the default permalink structure on each test before setUp() to allow
		// the tests to override it.
		$this->set_permalink_structure( Utils::DEFAULT_PERMALINK_STRUCTURE );

		parent::setUp();

		// Call the PHPUnit 8 'set_up' method if it exists.
		if ( method_exists( $this, 'set_up' ) ) {
			$this->set_up();
		}

		if ( ! $this->app ) {
			$this->refresh_application();
		}

		// Clear the test factory.
		static::$factory = null;

		$this->hooks_set_up();

		$this->clean_up_global_scope();

		// Boot traits on the test case.
		static::get_test_case_traits()
			->each(
				function( $trait ) {
					$method = strtolower( class_basename( $trait ) ) . '_set_up';

					if ( method_exists( $this, $method ) ) {
						$this->{$method}();
					}
				}
			);

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		add_filter( 'wp_die_handler', [ WP_Die::class, 'get_handler' ] );
	}

	/**
	 * After a test method runs, reset any state in WordPress the test method might have changed.
	 */
	protected function tearDown(): void {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride,WordPress.NamingConventions.PrefixAllGlobals
		global $wp_query, $wp;

		// Call the test case's "tear_down" method if it exists.
		if ( method_exists( $this, 'tear_down' ) ) {
			$this->tear_down();
		}

		static::get_test_case_traits()
			// Tearing down requires performing priority traits in opposite order.
			->reverse()
			->each(
				function( $trait ) {
					$method = strtolower( class_basename( $trait ) ) . '_tear_down';

					if ( method_exists( $this, $method ) ) {
						$this->{$method}();
					}
				}
			);

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

		if ( $this->app ) {
			$this->app = null;

			if ( class_exists( Facade::class ) ) {
				Facade::set_facade_application( null );
			}
		}
	}

	/**
	 * Get the test case traits.
	 *
	 * @return Collection
	 */
	protected static function get_test_case_traits(): Collection {
		// Boot traits on the test case.
		$traits = array_values( class_uses_recursive( static::class ) );

		$priority_traits = static::get_priority_traits();

		// Combine the priority and non-priority traits.
		return collect()
			->merge( array_intersect( $priority_traits, $traits ) )
			->merge( array_diff( $traits, $priority_traits ) )
			->unique();
	}

	/**
	 * Get an array of priority traits.
	 *
	 * @return array
	 */
	protected static function get_priority_traits(): array {
		return [
			// This order is deliberate.
			Refresh_Database::class,
			WordPress_Authentication::class,
			Admin_Screen::class,
			Network_Admin_Screen::class,
		];
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

		if ( class_exists( Facade::class ) ) {
			Facade::set_facade_application( $this->app );
			Facade::clear_resolved_instances();
		}

		if ( class_exists( Alias_Loader::class ) ) {
			Alias_Loader::set_instance( null );
		}

		Model::set_event_dispatcher( $this->app['events'] );
	}

	/**
	 * Fetches the factory object for generating WordPress fixtures.
	 *
	 * @return \Mantle\Testing\Factory\Factory_Container
	 */
	protected static function factory() {
		if ( ! isset( static::$factory ) ) {
			static::$factory = new Factory_Container( Container::getInstance() );
		}

		return static::$factory;
	}

	/**
	 * Allow the factory to be checked against.
	 *
	 * @param string $name Property name.
	 * @return boolean
	 */
	public function __isset( $name ) {
		return 'factory' === $name;
	}

	/**
	 * Retrieve the factory instance non-statically.
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( 'factory' === $name ) {
			return self::factory();
		}
	}
}
