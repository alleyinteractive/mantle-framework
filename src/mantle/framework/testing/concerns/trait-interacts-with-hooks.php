<?php
/**
 * Interacts_With_Hooks trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Assertions and interactions with WordPress hooks.
 */
trait Interacts_With_Hooks {
	/**
	 * Storage of the hooks that have been fired.
	 *
	 * @var array
	 */
	protected $hooks_fired = [];

	/**
	 * Setup the trait listener.
	 *
	 * @return void
	 */
	public function interacts_with_hooks_set_up(): void {
		\add_filter(
			'all',
			function( $value ) {
				$filter = current_filter();

				if ( ! isset( $this->hooks_fired[ $filter ] ) ) {
					$this->hooks_fired[ $filter ] = 0;
				}

				$this->hooks_fired[ $filter ]++;
				return $value;
			}
		);
	}

	/**
	 * Tear down the trait.
	 *
	 * @return void
	 */
	public function interacts_with_hooks_tear_down(): void {
		$this->hooks_fired = [];
	}

	/**
	 * Assert if a hook (action/filter) was applied.
	 *
	 * @param string $hook Hook to check against.
	 * @param int    $count Count to compare.
	 * @return void
	 */
	public function assertHookApplied( string $hook, int $count = null ): void {
		PHPUnit::assertTrue( ! empty( $this->hooks_fired[ $hook ] ) );

		if ( null !== $count ) {
			$times_fired = $this->hooks_fired[ $hook ] ?? 0;
			PHPUnit::assertEquals(
				$count,
				$this->hooks_fired[ $hook ],
				"Asserted that [{$hook}] was fired {$count} times when only fired {$times_fired} times."
			);
		}
	}

	/**
	 * Assert if a hook (action/filter) was not applied.
	 *
	 * @param string $hook Hook to check against.
	 * @return void
	 */
	public function assertHookNotApplied( string $hook ): void {
		PHPUnit::assertTrue( empty( $this->hooks_fired[ $hook ] ) );
	}

	public function expectAction( string $hook ) {

	}

	public function expectFilter( string $hook ) {

	}

}
