<?php
/**
 * Installation_Manager class file
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Singleton;

/**
 * Installation Manager
 */
class Installation_Manager {
	use Conditionable;
	use Concerns\PHPUnit_Upgrade_Warning;
	use Concerns\Rsync_Installation;
	use Singleton;

	/**
	 * Callbacks for before installation.
	 *
	 * @var callable[]
	 */
	protected array $before_install_callbacks = [];

	/**
	 * Callbacks for after installation.
	 *
	 * @var callable[]
	 */
	protected array $after_install_callbacks = [];

	/**
	 * Callback for after WordPress is loaded.
	 *
	 * @var callable[]
	 */
	protected array $after_loaded_callbacks = [];

	/**
	 * Constructor.
	 *
	 * Ensure that any environment variables also call the subsequent methods to
	 * configure the installation.
	 */
	public function __construct() {
		$this->with_default_exclusions();

		if ( Utils::env_bool( 'MANTLE_INSTALL_VIP_MU_PLUGINS', false ) ) {
			$this->with_vip_mu_plugins();
		}

		if ( Utils::env_bool( 'MANTLE_INSTALL_OBJECT_CACHE', false ) ) {
			$this->with_object_cache();
		} elseif ( $object_cache = Utils::env( 'MANTLE_INSTALL_OBJECT_CACHE', false ) ) {
			$this->with_object_cache( $object_cache );
		}

		if ( Utils::env_bool( 'MANTLE_USE_SQLITE', false ) ) {
			$this->with_sqlite();
		}
	}

	/**
	 * Define a callback to be invoked before installation.
	 *
	 * @param callable|null $callback Callback to invoke before installation.
	 * @return static
	 */
	public function before( ?callable $callback ) {
		if ( is_callable( $callback ) ) {
			$this->before_install_callbacks[] = $callback;
		}

		return $this;
	}

	/**
	 * Define a callback to be invoked after installation.
	 *
	 * @param callable|null $callback Callback to invoke after installation.
	 * @param bool          $append Whether to append the callback to the list or prepend it.
	 * @return static
	 */
	public function after( ?callable $callback, bool $append = true ) {
		if ( is_callable( $callback ) ) {
			$append
				? $this->after_install_callbacks[] = $callback
				: array_unshift( $this->after_install_callbacks, $callback );
		}

		return $this;
	}

	/**
	 * Define a callback for a specific WordPress hook.
	 *
	 * @param string   $hook Hook name.
	 * @param callable $callback Callback to invoke.
	 * @param int      $priority Priority.
	 * @param int      $accepted_args Number of accepted arguments.
	 * @return static
	 */
	public function on( string $hook, ?callable $callback, int $priority = 10, int $accepted_args = 1 ) {
		if ( is_callable( $callback ) ) {
			tests_add_filter( $hook, $callback, $priority, $accepted_args );
		}

		return $this;
	}

	/**
	 * Define a callback to be invoked using the 'muplugins_loaded' hook.
	 *
	 * @param callable $callback Callback to invoke on 'muplugins_loaded'.
	 * @return static
	 */
	public function loaded( ?callable $callback ) {
		return $this->on( 'muplugins_loaded', $callback );
	}

	/**
	 * Define a callback to be invoked on 'init'.
	 *
	 * @param callable $callback Callback to invoke on 'init'.
	 * @return static
	 */
	public function init( ?callable $callback ) {
		return $this->loaded(
			fn () => $this->on( 'init', $callback )
		);
	}

	/**
	 * Define the active theme to be set after the installation is loaded.
	 *
	 * @param string $theme Theme name.
	 * @return static
	 */
	public function theme( string $theme ) {
		return $this->loaded( fn () => switch_theme( $theme ) );
	}

	/**
	 * Alias for `theme()`.
	 *
	 * @param string $theme Theme name.
	 * @return static
	 */
	public function with_theme( string $theme ) {
		return $this->theme( $theme );
	}

	/**
	 * Define the active plugins to be set after the installation is loaded.
	 *
	 * To install a remote plugin to the installation during the rsync process,
	 * use the `install_plugin()` method.
	 *
	 * @see \Mantle\Testing\Concerns\Rsync_Installation::install_plugin()
	 *
	 * @param array<int, string> $plugins Plugin files to activate in WordPress.
	 * @return static
	 */
	public function plugins( array $plugins ) {
		return $this->loaded( fn () => update_option( 'active_plugins', $plugins ) );
	}

	/**
	 * Alias for `plugins()`.
	 *
	 * @param array<int, string> $plugins Plugin files to activate in WordPress.
	 * @return static
	 */
	public function with_plugins( array $plugins ) {
		return $this->plugins( $plugins );
	}

	/**
	 * Alias for `plugins()`.
	 *
	 * @param array<int, string> $plugins Plugin files to activate in WordPress.
	 */
	public function with_active_plugins( array $plugins ): static {
		return $this->plugins( $plugins );
	}

	/**
	 * Define if the testing suite should use the experimental feature that will
	 * use the site's home URL host as the HTTP host when making requests.
	 *
	 * Without enabling this feature, the HTTP host will be set to the value of
	 * the WP_TESTS_DOMAIN constant and all relative URLs will be calculated from
	 * that domain.
	 *
	 * In the next major release of Mantle, this feature will be enabled by default.
	 *
	 * @param bool $enable Whether to enable the experimental feature.
	 */
	public function with_experimental_testing_url_host( bool $enable = true ): static {
		return $this->before(
			fn () => putenv( 'MANTLE_EXPERIMENTAL_TESTING_USE_HOME_URL_HOST=' . ( $enable ? '1' : '0' ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
		);
	}

	/**
	 * Define a custom option to be set after the installation is loaded.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Option value.
	 */
	public function with_option( string $option, mixed $value ): static {
		return $this->loaded( fn () => update_option( $option, $value ) );
	}

	/**
	 * Define the site/home URLs to be set after the installation is loaded.
	 *
	 * @throws \InvalidArgumentException If the home or site URL is invalid.
	 *
	 * @param string|null $home Home URL.
	 * @param string|null $site Site URL.
	 * @param bool        $set_tests_domain Whether to set WP_TESTS_DOMAIN constant to match the home URL.
	 */
	public function with_url( ?string $home = null, ?string $site = null, bool $set_tests_domain = true ): static {
		if ( $home ) {
			if ( ! filter_var( $home, FILTER_VALIDATE_URL ) ) {
				throw new \InvalidArgumentException( 'Invalid home URL.' );
			}

			$this->with_option( 'home', $home );

			if ( $set_tests_domain ) {
				$this->before(
					fn () => defined( 'WP_TESTS_DOMAIN' ) || define( 'WP_TESTS_DOMAIN', parse_url( $home, PHP_URL_HOST ) ), // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
				);
			}
		}

		if ( $site ) {
			if ( ! filter_var( $site, FILTER_VALIDATE_URL ) ) {
				throw new \InvalidArgumentException( 'Invalid site URL.' );
			}

			$this->with_option( 'siteurl', $site );

			// Setup the default HTTP_HOST and HTTPS to make sure the site is installed properly.
			$this->before( function () use ( $site ): void {
				$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = parse_url( $site, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

				if ( 'https' === parse_url( $site, PHP_URL_SCHEME ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
					$_SERVER['HTTPS'] = 'on';

					defined( 'WP_TESTS_USE_HTTPS' ) || define( 'WP_TESTS_USE_HTTPS', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
				} else {
					unset( $_SERVER['HTTPS'] );
				}
			} );
		}


		return $this;
	}

	/**
	 * Enable or disable the debug mode.
	 *
	 * @param bool $enable Whether to enable debug mode.
	 */
	public function with_debug( bool $enable = true ): static {
		return $this->before(
			fn () => putenv( 'MANTLE_TESTING_DEBUG=' . ( $enable ? '1' : '0' ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
		);
	}

	/**
	 * Enable or disable multisite mode.
	 *
	 * This is commonly set on the CI matrix but made available here as well.
	 *
	 * @param bool $enable Whether to enable multisite.
	 */
	public function with_multisite( bool $enable = true ): static {
		return $this->before(
			function () use ( $enable ): void {
				if ( $enable ) {
					putenv( 'WP_MULTISITE=1' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
					putenv( 'WP_TESTS_MULTISITE=1' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
				} else {
					putenv( 'WP_MULTISITE=0' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
					putenv( 'WP_TESTS_MULTISITE=0' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
				}
			},
		);
	}

	/**
	 * Install the Mantle Testing Framework.
	 *
	 * @return static
	 */
	public function install() {
		$this->warn_if_phpunit_10_or_higher();

		require_once __DIR__ . '/core-polyfill.php';

		if ( Utils::is_debug_mode() ) {
			Utils::info( '🚨 Debug mode is enabled.' );
		}

		if ( $this->rsync_to ) {
			$this->perform_rsync_testsuite();
			return $this;
		}

		foreach ( $this->before_install_callbacks as $before_install_callback ) {
			$before_install_callback();
		}

		try {
			require_once __DIR__ . '/wordpress-bootstrap.php';
		} catch ( \Throwable $throwable ) {
			Utils::error( '🚨 Failed to load the WordPress installation. Exception thrown:' );
			Utils::code( $throwable->getMessage() );
			exit( 1 );
		}

		foreach ( $this->after_install_callbacks as $after_install_callback ) {
			$after_install_callback();
		}

		return $this;
	}
}
