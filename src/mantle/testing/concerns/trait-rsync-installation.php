<?php
/**
 * Rsync_Installation trait file
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
	 * @param string $to Location to rsync to within `wp-content`.
	 * @param string $from Location to rsync from.
	 * @return static
	 */
	public function rsync( string $to = null, string $from = null ) {
		$this->rsync_to   = $to ?: $this->get_installation_path() . '/wp-content/plugins/plugin';
		$this->rsync_from = $from ?: getcwd();

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
	 * @param string $name Name of the plugin to use.
	 * @param string $from Location to rsync from.
	 */
	public function maybe_rsync_plugin( string $name = 'plugin', string $from = null ) {
		// Check if we are under an existing WordPress installation.
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		return $this->rsync( "plugins/{$name}", $from );
	}

	/**
	 * Maybe rsync the codebase as a theme within WordPress.
	 *
	 * @param string $name Name of the plugin to use.
	 * @param string $from Location to rsync from.
	 */
	public function maybe_rsync_theme( string $name = 'theme', string $from = null ) {
		// Check if we are under an existing WordPress installation.
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		return $this->rsync( "themes/{$name}", $from );
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
	 * @return void
	 */
	protected function rsync_before_install() {
		require_once __DIR__ . '/../class-utils.php';

		$base_install_path = $this->get_installation_path();

		// Normalize the rsync destination.
		$this->rsync_to = is_dir( $this->rsync_to ) ? $this->rsync_to : "$base_install_path/wp-content/{$this->rsync_to}";

		defined( 'WP_TESTS_INSTALL_PATH' ) || define( 'WP_TESTS_INSTALL_PATH', $base_install_path );

		// TEMP
		system( "rm -rf {$base_install_path}" );

		// Install WordPress at the base installation path if it doesn't exist yet.
		if ( ! is_dir( $base_install_path ) ) {
			echo "Installing WordPress at [{$base_install_path}]...\n";


			// Create the installation directory.
			if ( ! is_dir( $base_install_path ) && ! mkdir( $base_install_path, 0777, true ) ) {
				throw new \RuntimeException( "Unable to create directory [{$base_install_path}]." );
			}

			$cmd = sprintf(
				'export WP_CORE_DIR=%s && curl -s %s | bash -s %s %s %s %s %s %s',
				$base_install_path,
				'https://raw.githubusercontent.com/alleyinteractive/mantle-ci/HEAD/install-wp-tests.sh',
				// 'https://raw.githubusercontent.com/alleyinteractive/mantle-ci/debug/install-wp-tests.sh',
				// 'http://localhost:3030/install-wp-tests.sh',
				Utils::shell_safe( defined( 'DB_NAME' ) ? DB_NAME : Utils::env( 'WP_DB_NAME', 'wordpress_unit_tests' ) ),
				Utils::shell_safe( defined( 'DB_USER' ) ? DB_USER : Utils::env( 'WP_DB_USER', 'root' ) ),
				Utils::shell_safe( defined( 'DB_PASSWORD' ) ? DB_PASSWORD : Utils::env( 'WP_DB_PASSWORD', 'root' ) ),
				Utils::shell_safe( defined( 'DB_HOST' ) ? DB_HOST : Utils::env( 'WP_DB_HOST', 'localhost' ) ),
				Utils::shell_safe( Utils::env( 'WP_VERSION', 'latest' ) ),
				Utils::shell_safe( Utils::env( 'WP_SKIP_DB_CREATE', 'false' ) ),
			);

			$resp = system( $cmd, $retval );
			// $resp = system( $cmd, $retval );

			dd('RESP', $cmd, $resp);

			if ( 0 !== $retval ) {
				echo "\n🚨 Error installing WordPress!\nResponse from installation command:\n\n$resp\n" . PHP_EOL;
				exit( 1 );
			}

			echo "WordPress installed at [{$base_install_path}]...\n";
		} else {
			echo "WordPress already installed at [{$base_install_path}]...\n";
		}

		echo "Rsyncing the code from [{$this->rsync_from}] to [{$this->rsync_to}]...\n";

		if ( ! is_dir( $this->rsync_to ) && ! mkdir( $this->rsync_to, 0777, true ) ) {
			throw new \RuntimeException( "Unable to create destination directory [{$this->rsync_to}]." );
		}

		$cmd = implode(
			' ',
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
			]
		);

		dd('CMD', $cmd);

		exit;
	}
}
