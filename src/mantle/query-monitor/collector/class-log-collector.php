<?php
/**
 * Log_Collector class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor\Collector;

use Mantle\Contracts\Application;
use Mantle\Http\Request;
use Mantle\Log\Events\Message_Logged;
use Monolog\Logger;

/**
 * Log Collector
 */
class Log_Collector extends \QM_Collector {
	/**
	 * Collector ID
	 *
	 * @var string
	 */
	public $id = 'mantle-logs';

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;

		$this->app['log']->listen(
			function( Message_Logged $event ) {
				$trace = new \QM_Backtrace(
					[
						'ignore_frames' => 6,
					]
				);

				$this->data['logs'][] = [
					'message' => $event->message,
					'context' => $event->context,
					'trace'   => $trace,
					'level'   => $event->level,
					'type'    => 'log',
				];
			}
		);
	}

	/**
	 * Setup the collector data.
	 */
	public function process() {
		if ( empty( $this->data['logs'] ) ) {
			return;
		}

		$components = [];

		foreach ( $this->data['logs'] as $row ) {
			$component                      = $row['trace']->get_component();
			$components[ $component->name ] = $component->name;
		}

		$this->data['components'] = $components;
	}

	/**
	 * Retrieve log levels.
	 *
	 * @return array
	 */
	public function get_levels(): array {
		return Logger::getLevels();
	}

	/**
	 * Retrieve warning log levels.
	 *
	 * @return array
	 */
	public function get_warning_levels(): array {
		return [
			'WARNING',
			'ERROR',
			'CRITICAL',
			'ALERT',
			'EMERGENCY',
		];
	}
}
