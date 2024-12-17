<?php
/**
 * Expectation class file.
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Expectation;

use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use SebastianBergmann\Exporter\Exporter;

/**
 * Expectation for an action to be added/applied.
 */
class Expectation {
	/**
	 * Arguments applied with the hook.
	 *
	 * @var mixed
	 */
	// protected $args;

	/**
	 * Callback to validate the arguments match given expectations.
	 *
	 * @var null|callable(...$args): bool
	 */
	protected $argument_comparison_callback = null;

	/**
	 * Expected number of times for the hook to execute.
	 */
	protected int|null $times = null;

	/**
	 * Return value comparison callback.
	 *
	 * @var null|callable(...$args): bool
	 */
	protected $return_comparison_callback;

	/**
	 * Record of the start value of the hook
	 *
	 * @var array<array>
	 */
	protected $record_start = [];

	/**
	 * Record of the return value for a hook.
	 *
	 * @var array<array>
	 */
	protected $record_stop = [];

	/**
	 * Constructor.
	 *
	 * @param Action $action Action type to check (added/applied).
	 * @param string $hook Hook to listen to.
	 * @param mixed  $args Arguments for the hook.
	 */
	public function __construct(
		protected readonly Action $action,
		protected readonly string $hook,
		// protected ?array $args = null,
	) {
		// if ( ! empty( $args ) ) {
		// 	$this->args = $args;
		// }

		if ( Action::APPLIED === $action ) {
			$this->setup_applied_hooks();
		}
	}

	/**
	 * Setup listeners for the applied action.
	 */
	protected function setup_applied_hooks() {
		add_action( // @phpstan-ignore-line Action callback
			$this->hook,
			[ $this, 'record_start' ],
			PHP_INT_MIN,
			99
		);

		add_action( // @phpstan-ignore-line Action callback
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
	 */
	public function validate(): void {
		match ( $this->action ) {
			Action::APPLIED => $this->validate_applied(),
			Action::ADDED   => $this->validate_added(),
		};
	}

	/**
	 * Validate the applied action.
	 */
	protected function validate_applied(): void {
		$exporter = new Exporter();

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
		if ( isset( $this->argument_comparison_callback ) ) {
			foreach ( $this->record_start as $record ) {
				PHPUnit::assertTrue(
					call_user_func( $this->argument_comparison_callback, $record ),
					sprintf(
						'Failed asserting that hook [%s] argument passed %s matches the expected arguments.',
						$this->hook,
						$exporter->export( $record ),
					),
				);
			}
		}

		// Compare the return value of the hook.
		if ( isset( $this->return_comparison_callback ) ) {
			foreach ( $this->record_stop as $record ) {
				PHPUnit::assertTrue(
					call_user_func_array( $this->return_comparison_callback, $record ),
					sprintf(
						'Failed asserting that hook\'s [%s] return value %s matches the expected return value.',
						$this->hook,
						$exporter->export( $record ),
					)
				);
			}
		}

		// Remove the actions for the hook.
		remove_action( $this->hook, [ $this, 'record_start' ], PHP_INT_MIN );
		remove_action( $this->hook, [ $this, 'record_stop' ], PHP_INT_MAX );
	}

	/**
	 * Validate if a hook was added with an optional given callback.
	 *
	 * @todo Validate callback/args.
	 */
	protected function validate_added(): void {
		PHPUnit::assertTrue(
			(bool) has_action( $this->hook, $this->args ?? false ),
			"Expected that hook [{$this->hook}] would have action added."
		);

		global $wp_filter;
		if ( isset( $this->argument_comparison_callback ) ) {
			dd($wp_filter[$this->hook], $this->argument_comparison_callback);
			// foreach
			// PHPUnit::assertTrue(
			// 	call_user_func()
			// 	$this->args === current_filter(),
			// 	"Expected that hook [{$this->hook}] would have action added with the given callback."
			// );
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
	 */
	public function times( int $times ): static {
		$this->times = $times;

		return $this;
	}

	/**
	 * Specify the arguments for the expectation.
	 *
	 * If you want to specify a callback to check the arguments, use the
	 * `withArgs` method instead.
	 *
	 * @param mixed ...$values Values to check.
	 */
	public function with( mixed ...$values ): static {
		return $this->withArgs(
			function ( array $arguments ) use ( $values ): bool {
				PHPUnit::assertSame(
					$values,
					$arguments,
					sprintf(
						'Failed asserting that hook\'s [%s] arguments %s matches the expected arguments.',
						$this->hook,
						( new Exporter() )->export( $arguments ),
					)
				);

				return true;
			},
		);
	}

	/**
	 * Specify the callback that will be used to compare the arguments for the action.
	 *
	 * @param callable(array $args): bool $callback Callback to compare the arguments.
	 */
	public function withArgs( callable $callback ): static {
		$this->argument_comparison_callback = $callback;

		return $this;
	}

	/**
	 * Remove checking the arguments for the action.
	 */
	public function withAnyArgs(): static {
		unset( $this->argument_comparison_callback );

		return $this;
	}

	/**
	 * Specify that the filter returns a specific value.
	 *
	 * @param mixed ...$values Values to return.
	 */
	public function andReturn( mixed ...$values ): static {
		return $this->return_comparison(
			function ( $value ) use ( $values ) {
				foreach ( $values as $expected ) {
					if ( is_callable( $expected ) ) {
						return (bool) $expected( $value );
					}

					if ( $value === $expected ) {
						return true;
					}
				}

				return false;
			}
		);
	}

	/**
	 * Specify that the filter returns null.
	 */
	public function andReturnNull(): static {
		return $this->andReturn( null );
	}

	/**
	 * Specify that the filter returns false.
	 */
	public function andReturnFalse(): static {
		return $this->andReturn( false );
	}

	/**
	 * Specify that the filter returns true.
	 */
	public function andReturnTrue(): static {
		return $this->andReturn( true );
	}

	/**
	 * Specify that the filter returns a truthy value.
	 */
	public function andReturnTruthy(): static {
		return $this->return_comparison( fn ( $value ) => (bool) $value );
	}

	/**
	 * Specify that the filter returns a falsy value.
	 */
	public function andReturnFalsy(): static {
		return $this->return_comparison( fn ( $value ) => ! $value );
	}

	/**
	 * Specify that the filter returns a boolean value.
	 */
	public function andReturnBoolean(): static {
		return $this->andReturn( true, false );
	}

	/**
	 * Specify that the filter returns an empty value.
	 */
	public function andReturnEmpty(): static {
		return $this->return_comparison( fn ( $value ) => empty( $value ) );
	}

	/**
	 * Specify that the filter returns a non-empty value.
	 */
	public function andReturnNotEmpty(): static {
		return $this->return_comparison( fn ( $value ) => ! empty( $value ) );
	}

	/**
	 * Specify that the filter returns an array value.
	 */
	public function andReturnArray(): static {
		return $this->return_comparison( fn ( $value ) => is_array( $value ) );
	}

	/**
	 * Specify that the filter returns an instance of a class.
	 *
	 * @param string $class Class name.
	 */
	public function andReturnInstanceOf( string $class ): static {
		return $this->return_comparison( fn ( $value ) => $value instanceof $class );
	}

	/**
	 * Specify that the filter returns a string value.
	 */
	public function andReturnString(): static {
		return $this->return_comparison( fn ( $value ) => is_string( $value ) );
	}

	/**
	 * Specify that the filter returns an integer value.
	 */
	public function andReturnInteger(): static {
		return $this->return_comparison( fn ( $value ) => is_int( $value ) );
	}

	/**
	 * Specify the return comparison callback for the filter.
	 *
	 * @param callable(mixed $value): bool $callback Callback called to compare the return value.
	 */
	protected function return_comparison( callable $callback ): static {
		$this->return_comparison_callback = $callback;

		return $this;
	}
}
