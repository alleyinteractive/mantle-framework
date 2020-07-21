<?php
/**
 * Query_Monitor_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Query_Monitor;

use Mantle\Framework\Service_Provider;
use QM_Collectors;

use function SML\remove_action_validated;

/**
 * Docs Service Provider
 */
class Query_Monitor_Service_Provider extends Service_Provider {
	/**
	 * Callbacks to fire to dispatch Query Monitor.
	 *
	 * @var array
	 */
	protected $query_monitor_dispatches = [];

	/**
	 * Register the Service Provider
	 */
	public function register() {
		\add_filter( 'qm/dispatchers', [ $this, 'fix_query_monitor_dispatcher' ], PHP_INT_MAX );
		\add_filter( 'qm/collectors', [ $this, 'register_collector' ] );
		\add_filter( 'qm/outputter/html', [ $this, 'output' ], 60, 2 );
	}

	/**
	 * Fix the Query Monitor Dispatcher to properly fire in Mantle.
	 *
	 * @param \QM_Dispatcher[] $dispatchers Array of dispatchers.
	 * @return \QM_Dispatcher[]
	 */
	public function fix_query_monitor_dispatcher( $dispatchers ) {
		foreach ( [ 'ajax', 'html', 'wp_die' ] as $dispatcher ) {
			if ( isset( $dispatchers[ $dispatcher ] ) ) {
				$this->query_monitor_dispatches[] = [ $dispatchers[ $dispatcher ], 'dispatch' ];
			}
		}

		return $dispatchers;
	}

	/**
	 * Fire the Query Monitor dispatches and return the response.
	 *
	 * @return string|null
	 */
	public function fire_query_monitor_dispatches(): ?string {
		if ( empty( $this->query_monitor_dispatches ) ) {
			return null;
		}

		ob_start();

		foreach ( $this->query_monitor_dispatches as $callback ) {
			// Remove the dispatcher from the 'shutdown' hook.
			remove_action_validated( 'shutdown', $callback, 0 );
			$callback();
		}

		return (string) ob_get_clean();
	}

	public function register_collector( array $collectors ) {
		$collectors['mantle'] = new Collector( $this->app );
		return $collectors;
	}

	public function output( $output ) {
		$collector = QM_Collectors::get( 'mantle' );

		if ( $collector ) {
			$output['mantle'] = new Output( $collector );
		}

		return $output;
	}
	/**
	 * Register the service provider.
	 */
	// public function boot() {
	// 	$this->set_files();
	// 	if ( $this->should_register_menu() ) {

	// 		\add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
	// 	}
	// }
}
