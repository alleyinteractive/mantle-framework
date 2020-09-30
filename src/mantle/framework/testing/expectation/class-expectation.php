<?php
namespace Mantle\Framework\Testing\Expectation;
use PHPUnit\Framework\Assert as PHPUnit;

class Expectation {
	protected $action;
	protected $hook;
	protected $args;

	protected $times;

	protected $return_value = '_undefined_';
	protected $record_start = [];
	protected $record_stop = [];

	public function __construct( string $action, string $hook, array $args = null ) {
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
	 * Validate if an expectation
	 *
	 * @return void
	 */
	public function validate() {
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

		if ( null !== $this->args ) {
			foreach ( $this->record_start as $record ) {
				/*
				Failed asserting that Array &0 (
    0 => 'value_to_check'
    1 => 'secondary_value_to_check'
) is identical to Array &0 (
    0 => 12
    1 => 3
).
*/
				PHPUnit::assertSame( $this->args, $record );
			}
		}

		if ( '_undefined_' !== $this->return_value ) {
			foreach ( $this->record_stop as $record ) {
				PHPUnit::assertSame( [ $this->return_value ], $record );
			}
		}

		// Remove the actions for the hook.
		if ( Expectation_Container::ACTION_APPLIED === $this->action ) {
			remove_action( $this->hook, [ $this, 'record_start' ], -1 );
			remove_action( $this->hook, [ $this, 'record_stop' ], PHP_INT_MAX );
		}
	}

	public function never() {
		$this->times = 0;
		return $this;
	}

	public function once() {
		$this->times = 1;
		return $this;
	}

	public function twice() {
		$this->times = 2;
		return $this;
	}

	public function times( int $times ) {
		$this->times = $times;
		return $this;
	}

	public function with( ...$args ) {
		$this->args = $args;
		return $this;
	}

	public function withAnyArgs() {
		$this->args = null;
		return $this;
	}

	public function andReturn( $value ) {
		$this->return_value = $value;
		return $this;
	}

	public function andReturnNull() {
		return $this->andReturn( null );
	}

	public function andReturnFalse() {
		return $this->andReturn( false );
	}

	public function andReturnTrue() {
		return $this->andReturn( true );
	}
}
