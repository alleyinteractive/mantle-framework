<?php
/**
 * Query_Monitor_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor;

use Mantle\Support\Service_Provider;
use QM_Collectors;

/**
 * Query Monitor Service Provider
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

		$this->app->booting( fn() => Timing::start( 'Mantle: Booting' ) );
		$this->app->booted( fn() => Timing::stop( 'Mantle: Booting' ) );
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
			remove_action( 'shutdown', $callback, 0 );
			$callback();
		}

		return (string) ob_get_clean();
	}

	/**
	 * Register collector to Query Monitor.
	 *
	 * @param array $collectors Collectors.
	 * @return array
	 */
	public function register_collector( array $collectors ) {
		$collectors['mantle']         = new Collector\Collector( $this->app );
		$collectors['mantle-headers'] = new Collector\Header_Collector( $this->app );
		$collectors['mantle-logs']    = new Collector\Log_Collector( $this->app );
		return $collectors;
	}

	/**
	 * Register the output class.
	 *
	 * @param array $output Output classes.
	 * @return array
	 */
	public function output( $output ) {
		$collector = QM_Collectors::get( 'mantle' );

		if ( $collector ) {
			$output['mantle'] = new Output\Output( $collector );
		}

		$collector = QM_Collectors::get( 'mantle-headers' );
		if ( $collector ) {
			$output['mantle-headers'] = new Output\Output_Headers( $collector );
		}

		$collector = QM_Collectors::get( 'mantle-logs' );
		if ( $collector ) {
			$output['mantle-logs'] = new Output\Output_Logs( $collector );
		}

		return $output;
	}
}
