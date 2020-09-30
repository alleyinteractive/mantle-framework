<?php
/**
 * Interacts_With_Hooks trait file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Concerns;

use Mantle\Framework\Testing\Expectation\Expectation;
use Mantle\Framework\Testing\Expectation\Expectation_Container;
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
	 * @var Expectation_Container
	 */
	protected $expectation_container;

	/**
	 * Setup the trait listener.
	 *
	 * @return void
	 */
	public function interacts_with_hooks_set_up(): void {
		$this->expectation_container = new Expectation_Container();

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

		$this->expectation_container->validate();
		$this->expectation_container = null;
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

	// public function expectActionAdded( string $hook, callable $callback = null ) {

	// }

	public function expectApplied( string $hook ): Expectation {
		return $this->expectation_container->add_applied( $hook );
	}

	// public function expectFilterAdded( string $hook ) {

	// }

	// public function expectFilterApplied( string $hook ) {

	// }

	// protected function add_hook_expectation()

	// /**
	//  * Fake a hook from being applied while allowing all other
	//  * hooks to function normally.
	//  *
	//  * @param string|string[] $hook Hooks to apply.
	//  * @return void
	//  */
	// public function fake( $hook ): void {
	// 	//  collect
	// }
}
