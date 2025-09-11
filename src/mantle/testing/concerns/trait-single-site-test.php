<?php
/**
 * Single_Site_Test trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Framework\Attributes\Before;

/**
 * Trait to ensure the request is made in single site mode and skipped otherwise.
 */
trait Single_Site_Test {
	/**
	 * Setup the trait.
	 *
	 * @before
	 */
	#[Before]
	public function single_site_test_set_up(): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'This test requires single site.' );
		}
	}
}
