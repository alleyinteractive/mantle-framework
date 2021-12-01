<?php
/**
 * Event_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Events\Dispatcher;
use Mantle\Support\Service_Provider;

/**
 * Event Service Provider
 */
class Event_Service_Provider extends Service_Provider {
	/**
	 * The event listener mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [
		'init' => [
			\class_to_fire::class,
		],
	];

	/**
	 * Register the application's event listeners.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->booting( function() {
			$events = $this->get_events();
			//
		} );
	}

	/**
	 * Get discovered events and listeners for the application.
	 *
	 * @todo Add event caching.
	 * @return array
	 */
	public function get_events(): array {
		return array_merge_recursive(
			$this->get_discovered_events(),
			$this->get_listen(),
		);
	}

	/**
	 * Get discovered events.
	 *
	 * @return array
	 */
	protected function get_discovered_events(): array {
		return [];
	}

	/**
	 * Get event events and handlers.
	 *
	 * @return array
	 */
	protected function get_listen(): array {
		return $this->listen;
	}

	/**
	 * Flag if Mantle should discover events automatically.
	 *
	 * @return bool
	 */
	public function should_discover_events(): bool {
		return false;
	}
}
