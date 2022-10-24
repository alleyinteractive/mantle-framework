<?php
/**
 * Installation_Manager class file
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Support\Traits\Singleton;

/**
 * Installation Manager
 */
class Installation_Manager {
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
	 * Define a callback to be invoked before installation.
	 *
	 * @param callable $callback Callback to invoke before installation.
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
	 * @param callable $callback Callback to invoke after installation.
	 * @return static
	 */
	public function after( ?callable $callback ) {
		if ( is_callable( $callback ) ) {
			$this->after_install_callbacks[] = $callback;
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
	 * Install the Mantle Testing Framework.
	 *
	 * @return static
	 */
	public function install() {
		require_once __DIR__ . '/core-polyfill.php';

		if ( $this->rsync_to ) {
			$this->perform_rsync_testsuite();
			return;
		}

		foreach ( $this->before_install_callbacks as $callback ) {
			$callback();
		}

		try {
			require_once __DIR__ . '/wordpress-bootstrap.php';
		} catch ( \Throwable $throwable ) {
			Utils::error( '🚨 Failed to load the WordPress installation. Exception thrown:' );
			Utils::code( $throwable->getMessage() );
			exit( 1 );
		}

		foreach ( $this->after_install_callbacks as $callback ) {
			$callback();
		}

		return $this;
	}
}
