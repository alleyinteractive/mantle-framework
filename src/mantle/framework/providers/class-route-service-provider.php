<?php
/**
 * Route_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Support\Service_Provider;

/**
 * Route Service Provider
 *
 * @deprecated No longer needed in Mantle 1.1.0 but kept around for backwards compatibility. Remove in Mantle 2.0.0.
 */
class Route_Service_Provider extends Service_Provider implements Isolated_Service_Provider {
	/**
	 * Set a callback to determine if a request should be passed down to WordPress.
	 * Pass through to the new router method.
	 *
	 * @param callable $callback Callback to invoke.
	 */
	protected function set_pass_through_callback( callable $callback ) {
		$this->app['router']->pass_requests_to_wordpress( $callback );
	}

	/**
	 * Allow pass through requests to WordPress.
	 * Pass through to the new router method.
	 *
	 * @return static
	 */
	protected function allow_pass_through_requests() {
		$this->app['router']->pass_requests_to_wordpress( true );

		return $this;
	}

	/**
	 * Prevent pass through requests to WordPress.
	 * Pass through to the new router method.
	 *
	 * @return static
	 */
	protected function prevent_pass_through_requests() {
		$this->app['router']->pass_requests_to_wordpress( false );

		return $this;
	}
}
