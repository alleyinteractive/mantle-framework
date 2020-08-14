<?php
/**
 * Interacts_With_Cron trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Concerns;

/**
 * Concern for interacting with the WordPress cron and making assertions against
 * it. Also supports queued and scheduled jobs.
 */
trait Interacts_With_Cron {
	public function assertInCronQueue( string $action ): void {

	}

	public function assertNotInCronQueue( string $action ): void {

	}
}
