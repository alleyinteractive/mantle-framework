<?php
/**
 * Expectation class file.
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Expectation;

use PHPUnit\Framework\Assert as PHPUnit;
use SebastianBergmann\Exporter\Exporter;

/**
 * Expectation for an action to be added/applied.
 */
class Expectation {
	/**
	 * Action to expect.
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * Hook to compare.
	 *
	 * @var string
	 */
	protected $hook;

	/**
	 * Arguments for the hook.
	 *
	 * @var mixed
	 */
	protected $args;

	/**
	 * Number of times for the hook to execute.
	 *
	 * @var int|null
	 */
	protected $times;

	/**
	 * Return value comparison callback.
	 *
	 * @var callable
	 */
	protected $return_value_callback;

	/**
	 * Record of the start value of the hook
	 *
	 * @var array
	 */
	protected $record_start = [];

	/**
	 * Record of the return value for a hook.
	 *
	 * @var array
	 */
	protected $record_stop = [];

	/**
	 * Constructor.
	 *
	 * @param string $action Action to expect.
	 * @param string $hook Hook to listen to.
	 * @param mixed  $args Arguments for the hook.
	 */
	public function __construct( string $action, string $hook, $args = null ) {
		$this->action = $action;
		$this->hook   = $hook;

		if ( ! empty( $args ) ) {
			$this->args = $args;
		}

		if ( Expectation_Container::ACTION_APPLIED === $action ) {
			$this->setup_applied_hooks();
		}
	}

	/**
	 * Setup listeners for the applied action.
	 */
	protected function setup_applied_hooks() {
		add_action(
			$this->hook,
			[ $this, 'record_start' ],
			-1,
			99
		);

		add_action(
			$this->hook,
			[ $this, 'record_stop' ],
			PHP_INT_MAX,
			99
		);
	}

	/**
	 * Record the start of a hook being applied.
	 *
	 * @param array ...$args Hook arguments.
	 * @return mixed
	 */
	public function record_start( ...$args ) {
		$this->record_start[] = $args;
		return array_shift( $args );
	}

	/**
	 * Record the stop of a hook being applied.
	 *
	 * @param array ...$args Hook arguments.
	 * @return mixed
	 */
	public function record_stop( ...$args ) {
		$this->record_stop[] = $args;
		return array_shift( $args );
	}

	/**
	 * Validate if an expectation meets its expectations.
	 *
	 * @return void
	 */
	public function validate() {
		$exporter = new Exporter();

		if ( Expectation_Container::ACTION_APPLIED === $this->action ) {
			if ( null !== $this->times ) {
				$count = count( $this->record_start );

				PHPUnit::assertEquals(
					$this->times,
					$count,
					"Expected that hook [{$this->hook}] ({$count}) would be applied [{$this->times}] times."
				);
			} else {
				PHPUnit::assertTrue(
					! empty( $this->record_start ),
					"Expected that hook [{$this->hook}] would be applied at least once."
				);
			}

			// Compare the arguments for the hook.
			if ( null !== $this->args ) {
				foreach ( $this->record_start as $record ) {
					PHPUnit::assertSame(
						$this->args,
						$record,
						sprintf(
							'Failed asserting that hook [%s] argument passed %s is identical to %s.',
							$this->hook,
							$exporter->export( $record ),
							$exporter->export( $this->args )
						),
					);
				}
			}

			// Compare the return value of the hook.
			if ( isset( $this->return_value_callback ) ) {
				foreach ( $this->record_stop as $record ) {
					PHPUnit::assertTrue(
						call_user_func_array( $this->return_value_callback, $record ),
						sprintf(
							'Failed asserting that hook\'s [%s] return value %s matches the expected return value.',
							$this->hook,
							$exporter->export( $record ),
						)
					);
				}
			}

			// Remove the actions for the hook.
			remove_action( $this->hook, [ $this, 'record_start' ], -1 );
			remove_action( $this->hook, [ $this, 'record_stop' ], PHP_INT_MAX );
		}

		// Asset if the action was added.
		if ( Expectation_Container::ACTION_ADDED === $this->action ) {
			PHPUnit::assertTrue(
				! ! has_action( $this->hook, $this->args ?? false ),
				"Expected that hook [{$this->hook}] would have action added."
			);
		}
	}

	/**
	 * Assert that the action was never applied.
	 *
	 * @return static
	 */
	public function never() {
		$this->times = 0;
		return $this;
	}

	/**
	 * Assert that the action was applied once.
	 *
	 * @return static
	 */
	public function once() {
		$this->times = 1;
		return $this;
	}

	/**
	 * Assert that the action was applied twice.
	 *
	 * @return static
	 */
	public function twice() {
		$this->times = 2;
		return $this;
	}

	/**
	 * Assert that the action was applied a specific number of times.
	 *
	 * @param int $times Number of times.
	 * @return static
	 */
	public function times( int $times ): static {
		$this->times = $times;
		return $this;
	}

	/**
	 * Specify the arguments for the expectation.
	 *
	 * @param mixed ...$args Arguments.
	 * @return static
	 */
	public function with( ...$args ): static {
		$this->args = $args;
		return $this;
	}

	/**
	 * Remove checking the arguments for the action.
	 *
	 * @return static
	 */
	public function withAnyArgs(): static {
		$this->args = null;
		return $this;
	}

	/**
	 * Specify that the filter returns a specific value.
	 *
	 * @param mixed $value Return value.
	 * @return static
	 */
	public function andReturn( mixed $value ): static {
		return $this->returnComparison(
			fn ( $return_value ) => $return_value === $value
		);
	}

	/**
	 * Specify that the filter returns null.
	 *
	 * @return static
	 */
	public function andReturnNull(): static {
		return $this->andReturn( null );
	}

	/**
	 * Specify that the filter returns false.
	 *
	 * @return static
	 */
	public function andReturnFalse(): static {
		return $this->andReturn( false );
	}

	/**
	 * Specify that the filter returns true.
	 *
	 * @return static
	 */
	public function andReturnTrue(): static {
		return $this->andReturn( true );
	}

	/**
	 * Specify that the filter returns a truthy value.
	 *
	 * @return static
	 */
	public function andReturnTruthy(): static {
		return $this->returnComparison( fn ( $value ) => ! ! $value );
	}

	/**
	 * Specify that the filter returns a falsy value.
	 *
	 * @return static
	 */
	public function andReturnFalsy(): static {
		return $this->returnComparison( fn ( $value ) => ! $value );
	}

	/**
	 * Specify that the filter returns an empty value.
	 *
	 * @return static
	 */
	public function andReturnEmpty(): static {
		return $this->returnComparison( fn ( $value ) => empty( $value ) );
	}

	/**
	 * Specify that the filter returns a non-empty value.
	 *
	 * @return static
	 */
	public function andReturnNotEmpty(): static {
		return $this->returnComparison( fn ( $value ) => ! empty( $value ) );
	}

	/**
	 * Specify that the filter returns an array value.
	 *
	 * @return static
	 */
	public function andReturnArray(): static {
		return $this->returnComparison( fn ( $value ) => is_array( $value ) );
	}

	/**
	 * Specify that the filter returns an instance of a class.
	 *
	 * @param string $class Class name.
	 * @return static
	 */
	public function andReturnInstanceOf( string $class ): static {
		return $this->returnComparison( fn ( $value ) => $value instanceof $class );
	}

	/**
	 * Specify that the filter returns a string value.
	 *
	 * @return static
	 */
	public function andReturnString(): static {
		return $this->returnComparison( fn ( $value ) => is_string( $value ) );
	}

	/**
	 * Specify that the filter returns an integer value.
	 *
	 * @return static
	 */
	public function andReturnInteger(): static {
		return $this->returnComparison( fn ( $value ) => is_int( $value ) );
	}

	/**
	 * Specify the return comparison callback for the filter.
	 *
	 * @param callable $callback Callback.
	 * @return static
	 */
	protected function returnComparison( callable $callback ): static {
		$this->return_value_callback = $callback;
		return $this;
	}
}
