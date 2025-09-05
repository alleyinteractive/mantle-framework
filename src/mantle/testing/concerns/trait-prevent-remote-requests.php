<?php
/**
 * Prevent_Remote_Requests trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Mock_Http_Response;
use PHPUnit\Framework\Attributes\Before;

/**
 * Prevent remote requests from being made by providing a default response to
 * the remote request.
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Prevent_Remote_Requests {
	/**
	 * Setup the trait.
	 *
	 * @before
	 * @internal
	 */
	#[Before]
	public function before_prevent_remote_requests(): void {
		if ( ! $this->prevent_remote_requests ) {
			$this->prevent_stray_requests( new Mock_Http_Response() );
		}
	}
}
