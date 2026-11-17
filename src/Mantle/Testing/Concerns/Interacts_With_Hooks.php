<?php
/**
 * Interacts_With_Hooks trait file.
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Expectation\Expectation;
use Mantle\Testing\Expectation\Expectation_Container;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Assertions and interactions with WordPress hooks.
 */
trait Interacts_With_Hooks {
	/**
	 * Storage of the hooks that have been fired.
	 *
	 * @var array<string, int>
	 */
	protected array $hooks_fired = [];

	/**
	 * Expectation Container
	 */
	protected ?Expectation_Container $expectation_container;

	/**
	 * Setup the trait listener.
	 */
	public function interacts_with_hooks_set_up(): void {
		$this->expectation_container = new Expectation_Container();

		\add_filter(
			'all',
			function ( $value ) {
				$filter = current_filter();

				if ( ! $filter ) {
					return $value;
				}

				$this->hooks_fired[ $filter ] ??= 0;

				$this->hooks_fired[ $filter ]++;

				return $value;
			}
		);
	}

	/**
	 * Tear down the trait.
	 */
	public function interacts_with_hooks_tear_down(): void {
		$this->hooks_fired = [];

		if ( isset( $this->expectation_container ) ) {
			$this->expectation_container->tear_down();
			$this->expectation_container = null;
		}
	}

	/**
	 * Assert if a hook (action/filter) was applied.
	 *
	 * @param string $hook Hook to check against.
	 * @param int    $count Count to compare.
	 */
	public function assertHookApplied( string $hook, ?int $count = null ): void {
		PHPUnit::assertNotEmpty(
			$this->hooks_fired[ $hook ] ?? [],
			"Asserted that [{$hook}] was not fired."
		);

		if ( null !== $count ) {
			$times_fired = $this->hooks_fired[ $hook ];

			PHPUnit::assertEquals(
				$count,
				$times_fired,
				sprintf(
					'Asserted that [%s] was applied %d %s when only applied %d %s.',
					$hook,
					$count,
					1 === $count ? 'time' : 'times',
					$times_fired,
					1 === $times_fired ? 'time' : 'times',
				),
			);
		}
	}

	/**
	 * Assert if a hook (action/filter) was not applied.
	 *
	 * @param string $hook Hook to check against.
	 */
	public function assertHookNotApplied( string $hook ): void {
		PHPUnit::assertEquals(
			0,
			$this->hooks_fired[ $hook ] ?? 0,
			"Asserted that [{$hook}] was fired."
		);
	}

	/**
	 * Assert the number of callbacks on a hook that call a method on an object of a class.
	 *
	 * Useful to check that a feature adds each of its hooks once, without needing
	 * the object that added them. Closures and function callbacks are not counted.
	 *
	 * @param string   $hook       Hook name.
	 * @param string   $class_name Exact class of the callback object.
	 * @param string   $method     Callback method.
	 * @param int      $count      Expected number of callbacks.
	 * @param int|null $priority   Optional. Hook priority. Defaults to all priorities.
	 */
	public function assertHookCallbackCount( string $hook, string $class_name, string $method, int $count, ?int $priority = null ): void {
		PHPUnit::assertSame(
			$count,
			$this->count_hook_callbacks( $hook, $class_name, $method, $priority ),
			sprintf(
				'Asserted that [%s] has %d %s of %s::%s%s.',
				$hook,
				$count,
				1 === $count ? 'callback' : 'callbacks',
				$class_name,
				$method,
				null === $priority ? '' : " at priority {$priority}",
			),
		);
	}

	/**
	 * Count the callbacks on a hook that call a method on an object of a class.
	 *
	 * @param string   $hook       Hook name.
	 * @param string   $class_name Exact class of the callback object.
	 * @param string   $method     Callback method.
	 * @param int|null $priority   Optional. Hook priority. Defaults to all priorities.
	 */
	protected function count_hook_callbacks( string $hook, string $class_name, string $method, ?int $priority = null ): int {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return 0;
		}

		/** @var array<int, array<string, array{function: mixed, accepted_args: int}>> $callbacks */
		$callbacks = $wp_filter[ $hook ]->callbacks;

		if ( null !== $priority ) {
			$callbacks = [ $priority => $callbacks[ $priority ] ?? [] ];
		}

		$count = 0;

		foreach ( $callbacks as $priority_callbacks ) {
			foreach ( $priority_callbacks as $callback ) {
				$function = $callback['function'];

				if (
					is_array( $function )
					&& is_object( $function[0] )
					&& $class_name === $function[0]::class
					&& $method === $function[1]
				) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * Add expectation that an action applied.
	 *
	 * @param string $hook Action to listen to.
	 */
	public function expectApplied( string $hook ): Expectation {
		if ( ! $this->expectation_container ) {
			$this->expectation_container = new Expectation_Container();
		}

		return $this->expectation_container->add_applied( $hook );
	}

	/**
	 * Add expectation that an action added.
	 *
	 * @param string   $hook Action to listen to.
	 * @param callable $callback Callback to check was added, optional.
	 * @return Expectation
	 */
	public function expectAdded( string $hook, ?callable $callback = null ) {
		if ( ! $this->expectation_container ) {
			$this->expectation_container = new Expectation_Container();
		}

		return $this->expectation_container->add_added( $hook, $callback );
	}
}
