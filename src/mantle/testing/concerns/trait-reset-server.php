<?php
/**
 * Prevent_Remote_Requests trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Utils;

/**
 * Reset the server for each test case.
 *
 * @mixin \Mantle\Testing\Test_Case
 */
trait Reset_Server {
	/**
	 * Setup the trait.
	 */
	public function reset_server_set_up(): void {
		Utils::reset_server();
	}
}
