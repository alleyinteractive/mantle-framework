<?php
/**
 * Rsync_Installation trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

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
	 * @param string $from Location to rsync from
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
	 * @param string $from Location to rsync from
	 */
	public function maybe_rsync_theme( string $name = 'theme', string $from = null ) {
		// Check if we are under an existing WordPress installation.
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		return $this->rsync( "themes/{$name}", $from );
	}
}
