<?php
/**
 * Registers_Rest_Route trait file
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Concerns;

use RuntimeException;

/**
 * Manage the registration of a REST route.
 *
 * @mixin \Mantle\Http\Routing\Route
 */
trait Registers_Rest_Route {
	/**
	 * Check if the current route is a REST API route.
	 */
	public function is_rest_api_route(): bool {
		return isset( $this->action['namespace'] );
	}

	/**
	 * Register the REST route for the current route.
	 *
	 * @throws RuntimeException If the route is not a REST API route.
	 */
	public function register_rest_route(): void {
		if ( ! isset( $this->action['namespace'] ) ) {
			throw new RuntimeException( 'Route must have a namespace in the route action.' );
		}

		register_rest_route(
			$this->action['namespace'],
			$this->getPath(),
			$this->action,
			! empty( $this->action['override'] ),
		);
	}
}
