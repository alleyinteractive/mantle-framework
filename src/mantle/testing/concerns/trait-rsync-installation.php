<?php
/**
 * Rsync_Installation trait file
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Utils;

/**
 * Trait to manage rsync-ing the codebase to live within a WordPress
 * installation.
 *
 * WordPress plugins/themes commonly need live within within a valid WordPress
 * installation. This can allow for end-to-end testing of a WordPress
 * plugin/theme from code that is written in a standalone repository. For
 * example, a repository can contain only a theme. Mantle will internally rsync
 * the theme to a WordPress installation without needing to run a bash script.
 *
 * After the rsync is complete, PHPUnit will be rerun from the new location.
 */
trait Rsync_Installation {
	/**
	 * Storage location to rsync the codebase to.
	 *
	 * @var string
	 */
	protected ?string $rsync_to = null;

	/**
	 * Storage location to rsync the codebase from.
	 *
	 * @var string
	 */
	protected ?string $rsync_from = null;

	/**
	 * Rsync the code base to be located under a valid WordPress installation.
	 *
	 * By default, the codebase will be rsynced to the `wp-content` directory.
	 *
	 * @param string $to Location to rsync to within `wp-content`.
	 * @param string $from Location to rsync from.
	 * @return static
	 */
	public function rsync( string $to = null, string $from = null ) {
		$this->rsync_to   = $to ?: $this->get_installation_path() . '/wp-content/plugins/plugin';
		$this->rsync_from = $from ?: getcwd() . '/';

		return $this;
	}

	/**
	 * Rsync the code base to be located underneath a WordPress installation if it
	 * isn't already.
	 *
	 * @param string $to Location to rsync to.
	 * @param string $from Location to rsync from.
	 * @return static
	 */
	public function maybe_rsync( string $to = null, string $from = null ) {
		// Check if we are under an existing WordPress installation.
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		return $this->rsync( $to, $from );
	}

	/**
	 * Maybe rsync the codebase as a plugin within WordPress.
	 *
	 * By default, the from path will be rsynced to `wp-content/plugins/{directory_name}`.
	 *
	 * @param string $name Name of the plugin folder, optional.
	 * @param string $from Location to rsync from.
	 */
	public function maybe_rsync_plugin( string $name = null, string $from = null ) {
		if ( ! $name ) {
			$name = basename( getcwd() );
		}

		return $this->maybe_rsync( "plugins/{$name}/", $from );
	}

	/**
	 * Maybe rsync the codebase as a theme within WordPress.
	 *
	 * By default, the from path will be rsynced to `wp-content/themes/{directory_name}`.
	 *
	 * @param string $name Name of the theme folder, optional.
	 * @param string $from Location to rsync from.
	 */
	public function maybe_rsync_theme( string $name = null, string $from = null ) {
		if ( ! $name ) {
			$name = basename( getcwd() );
		}

		return $this->maybe_rsync( 'themes', $from );
	}

	/**
	 * Retrieve the default installation path to rsync to.
	 *
	 * @return string
	 */
	protected function get_installation_path(): string {
		return getenv( 'WP_CORE_DIR' ) ?: sys_get_temp_dir() . '/wordpress';
	}

	/**
	 * Check if the current installation is underneath an existing WordPress
	 * installation.
	 *
	 * @return bool
	 */
	protected function is_within_wordpress_install(): bool {
		return false !== strpos( __DIR__, '/wp-content/' );
	}

	/**
	 * Rsync the codebase before installation.
	 *
	 * This allows the plugin/theme project to properly situate itself within a
	 * WordPress installation without needing to rsync it manually.
	 */
	protected function rsync_testsuite() {
		require_once __DIR__ . '/../class-utils.php';

		$base_install_path = $this->get_installation_path();

		// Normalize the rsync destination.
		$this->rsync_to = is_dir( $this->rsync_to ) ? $this->rsync_to : "$base_install_path/wp-content/{$this->rsync_to}";

		// Define the constants relative to where the codebase is being rsynced to.
		defined( 'WP_TESTS_INSTALL_PATH' ) || define( 'WP_TESTS_INSTALL_PATH', $base_install_path );
		defined( 'WP_TESTS_CONFIG_FILE_PATH' ) || define( 'WP_TESTS_CONFIG_FILE_PATH', "{$base_install_path}/wp-tests-config.php" );
		defined( 'ABSPATH' ) || define( 'ABSPATH', ensure_trailingslash( $base_install_path ) );

		// Install WordPress at the base installation path if it doesn't exist yet.
		if ( ! is_dir( $base_install_path ) ) {
			Utils::info(
				"Installating WordPress at <em>{$base_install_path}</em> ...",
				'Install Rsync'
			);

			// Create the installation directory.
			if ( ! is_dir( $base_install_path ) && ! mkdir( $base_install_path, 0777, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
				Utils::error(
					"Unable to create the WordPress installation directory at <em>{$base_install_path}</em>",
					'Install Rsync'
				);

				exit( 1 );
			}

			Utils::install_wordpress( $base_install_path );

			Utils::success(
				"WordPress installed at <em>{$base_install_path}</em>",
				'Install Rsync'
			);
		} else {
			Utils::info(
				"WordPress already installed at <em>{$base_install_path}</em>",
				'Install Rsync'
			);
		}

		Utils::info(
			"Rsyncing <em>{$this->rsync_from}</em> to <em>{$this->rsync_to}</em>...",
			'Install Rsync'
		);

		if ( ! is_dir( $this->rsync_to ) && ! mkdir( $this->rsync_to, 0777, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			Utils::error(
				"Unable to create destination directory [{$this->rsync_to}]."
			);

			exit( 1 );
		}

		// Rsync the from folder to the destination.
		$output = Utils::command(
			[
				'rsync -aWq',
				'--no-compress',
				'--exclude .npm',
				'--exclude .git',
				'--exclude node_modules',
				'--exclude .composer',
				'--exclude .phpcs',
				'--exclude .buddy-tests',
				"{$this->rsync_from} {$this->rsync_to}",
			],
			$retval
		);

		if ( 0 !== $retval ) {
			Utils::error( '🚨 Error installing rsyncing! Output from command:', 'Install Rsync' );
			Utils::code( $output );
			exit( 1 );
		}

		Utils::success(
			"Rsynced to <em>{$this->rsync_to}</em> and changed working directory.",
			'Install Rsync'
		);

		chdir( $this->rsync_to );

		$command = $this->get_phpunit_command();

		// Proxy to the phpunit instance within the new rsynced WordPress installation.
		Utils::info(
			"Running <em>{$command}</em> in <em>{$this->rsync_to}</em>:",
			'Install Rsync'
		);

		system( $command, $result_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_system

		exit( (int) $result_code );
	}

	/**
	 * Generate the command that will be run inside the rsync-ed WordPress
	 * installation to fire off PHPUnit.
	 *
	 * @return string
	 */
	protected function get_phpunit_command(): string {
		$args = (array) $_SERVER['argv'] ?? []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Remove the first argument, which is the path to the phpunit binary.
		array_shift( $args );

		$executable = getenv( 'WP_PHPUNIT_PATH' ) ?: 'vendor/bin/phpunit';

		return $executable . ' ' . implode( ' ', array_map( 'escapeshellarg', $args ) );
	}
}
