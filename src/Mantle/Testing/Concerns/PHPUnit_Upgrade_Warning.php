<?php
/**
 * PHPUnit_Upgrade_Warning trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Deprecated;

/**
 * Previously used to warn about PHPUnit 10+ upgrade. No longer does anything and will be removed with Mantle 2.0.
 *
 * @deprecated 1.14.1
 */
trait PHPUnit_Upgrade_Warning {
	/**
	 * Silence the PHPUnit 10+ warning.
	 *
	 * No longer does anything, kept for backward compatibility.
	 */
	#[Deprecated( 'This method no longer does anything and will be removed with Mantle 2.0.' )]
	public function silence_phpunit_warning(): static {
		return $this;
	}
}
