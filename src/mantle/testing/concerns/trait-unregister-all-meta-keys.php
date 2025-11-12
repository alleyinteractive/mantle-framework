<?php
/**
 * Unregister_All_Meta_Keys trait file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

/**
 * Unregister all meta keys registered during a test.
 *
 * This was previously done in the root TestCase directly, but has been moved to
 * a trait to provide a better experience to users of the testing framework and
 * allow end users to opt-in if desired to this behavior. Unregistering all meta
 * keys usually isn't desired, especially if a test relies on meta keys
 * registered in other tests within the same suite. Mantle will instead opt-to
 * restore the meta keys to the state before the test ran for each run. All meta
 * keys registered during before a test runs (those registered on `init` for
 * example) will exist for all test runs.
 *
 * @see \Mantle\Testing\Concerns\Preserves_Globals
 *
 * @mixin \Mantle\Testing\TestCase
 */
trait Unregister_All_Meta_Keys {
	/**
	 * Tear down the unregister all meta keys concern.
	 */
	protected function unregister_all_meta_keys_tear_down(): void {
		static::unregister_all_meta_keys();
	}
}
