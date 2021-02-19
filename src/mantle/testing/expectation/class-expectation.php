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
	 * Return value for the action
	 *
	 * @var mixed
	 */
	protected $return_value = '_undefined_';

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
							'Failed asserting that hook [%] argument passed %s is identical to %s.',
							$this->hook,
							$exporter->export( $record ),
							$exporter->export( $this->args )
						),
					);
				}
			}

			// Compare the return value of the hook.
			if ( '_undefined_' !== $this->return_value ) {
				foreach ( $this->record_stop as $record ) {
					PHPUnit::assertSame(
						[ $this->return_value ],
						$record,
						sprintf(
							'Failed asserting that hook [%s] return value %s is identical to %s.',
							$this->hook,
							$exporter->export( $record ),
							$exporter->export( $this->return_value )
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
	public function times( int $times ) {
		$this->times = $times;
		return $this;
	}

	/**
	 * Specify the arguments for the expectation.
	 *
	 * @param mixed ...$args Arguments.
	 * @return static
	 */
	public function with( ...$args ) {
		$this->args = $args;
		return $this;
	}

	/**
	 * Remove checking the arguments for the action.
	 *
	 * @return static
	 */
	public function withAnyArgs() {
		$this->args = null;
		return $this;
	}

	/**
	 * Specify that the filter returns a specific value.
	 *
	 * @param mixed $value Return value.
	 * @return static
	 */
	public function andReturn( $value ) {
		$this->return_value = $value;
		return $this;
	}

	/**
	 * Specify that the filter returns null.
	 *
	 * @return static
	 */
	public function andReturnNull() {
		return $this->andReturn( null );
	}

	/**
	 * Specify that the filter returns false.
	 *
	 * @return static
	 */
	public function andReturnFalse() {
		return $this->andReturn( false );
	}

	/**
	 * Specify that the filter returns true.
	 *
	 * @return static
	 */
	public function andReturnTrue() {
		return $this->andReturn( true );
	}
}
