<?php
/**
 * Warn_Remote_Requests trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

/**
 * Warn if remote requests are being made. Does not provide a default response.
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Warn_Remote_Requests {
	/**
	 * Setup the trait.
	 */
	public function warn_remote_requests_set_up(): void {
		if ( ! $this->prevent_remote_requests ) {
			$this->prevent_stray_requests();
		}
	}
}
