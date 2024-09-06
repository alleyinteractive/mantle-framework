<?php

namespace Mantle\Testing\Parallel;

use Closure;
use Mantle\Contracts\Container;
use Mantle\Support\Str;
use Mantle\Testing\Test_Case;

/**
 * Parallel Testing Container
 */
class Parallel_Testing {
	/**
	 * The options resolver callback.
	 */
	// protected Closure|null $options_resolver = null;

	/**
	 * The token resolver callback.
	 */
	protected Closure|null $token_resolver = null;

	/**
	 * All of the registered "setUp" process callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $set_up_process_callbacks = [];

	/**
	 * All of the registered "setUp" test case callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $set_up_test_case_callbacks = [];

	/**
	 * All of the registered "setUp" test database callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $set_up_test_database_callbacks = [];

	/**
	 * All of the registered "tearDown" process callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $tear_down_process_callbacks = [];

	/**
	 * All of the registered "tearDown" test case callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $tear_down_test_case_callbacks = [];

	/**
	 * Create a new parallel testing instance.
	 *
	 * @param  Container $container
	 */
	public function __construct( protected Container $container ) {}

	/**
	 * Set a callback that should be used when resolving options.
	 *
	 * @param  Closure|null $resolver
	 * @return void
	 */
	// public function resolve_options_using( Closure|null $resolver ) {
	// 	$this->options_resolver = $resolver;
	// }

	/**
	 * Set a callback that should be used when resolving the unique process token.
	 *
	 * @param  Closure|null $resolver
	 * @return void
	 */
	public function resolve_token_using( Closure|null $resolver ) {
		$this->token_resolver = $resolver;
	}

	/**
	 * Register a "setUp" process callback.
	 *
	 * @param  Closure $callback
	 * @return void
	 */
	public function set_up_process( Closure $callback ) {
		$this->set_up_process_callbacks[] = $callback;
	}

	/**
	 * Register a "setUp" test case callback.
	 *
	 * @param  Closure $callback
	 * @return void
	 */
	public function set_up_test_case( Closure $callback ) {
		$this->set_up_test_case_callbacks[] = $callback;
	}

	/**
	 * Register a "setUp" test database callback.
	 *
	 * @param  Closure $callback
	 * @return void
	 */
	public function set_up_test_database( Closure $callback ) {
		$this->set_up_test_database_callbacks[] = $callback;
	}

	/**
	 * Register a "tearDown" process callback.
	 *
	 * @param  Closure $callback
	 * @return void
	 */
	public function tear_down_process( Closure $callback ) {
		$this->tear_down_process_callbacks[] = $callback;
	}

	/**
	 * Register a "tearDown" test case callback.
	 *
	 * @param  Closure $callback
	 * @return void
	 */
	public function tear_down_test_case( Closure $callback ) {
		$this->tear_down_test_case_callbacks[] = $callback;
	}

	/**
	 * Call all of the "setUp" process callbacks.
	 *
	 * @return void
	 */
	public function call_set_up_process_callbacks() {
		$this->when_running_in_parallel(
			function () {
				foreach ( $this->set_up_process_callbacks as $callback ) {
					$this->container->call(
						$callback,
						[
							'token' => $this->token(),
						]
					);
				}
			}
		);
	}

	/**
	 * Call all of the "setUp" test case callbacks.
	 *
	 * @param  Test_Case $test_case
	 * @return void
	 */
	public function call_set_up_test_case_callbacks( Test_Case $test_case ) {
		$this->when_running_in_parallel(
			function () use ( $test_case ) {
				foreach ( $this->set_up_test_case_callbacks as $callback ) {
					$this->container->call(
						$callback,
						[
							'test_case' => $test_case,
							'token'     => $this->token(),
						]
					);
				}
			}
		);
	}

	/**
	 * Call all of the "setUp" test database callbacks.
	 *
	 * @param  string $database
	 * @return void
	 */
	public function call_set_up_test_database_callbacks( string $database ) {
		$this->when_running_in_parallel(
			function () use ( $database ) {
				foreach ( $this->set_up_test_database_callbacks as $callback ) {
					$this->container->call(
						$callback,
						[
							'database' => $database,
							'token'    => $this->token(),
						]
					);
				}
			}
		);
	}

	/**
	 * Call all of the "tearDown" process callbacks.
	 *
	 * @return void
	 */
	public function call_tear_down_process_callbacks() {
		$this->when_running_in_parallel(
			function () {
				foreach ( $this->tear_down_process_callbacks as $callback ) {
					$this->container->call(
						$callback,
						[
							'token' => $this->token(),
						]
					);
				}
			}
		);
	}

	/**
	 * Call all of the "tearDown" test case callbacks.
	 *
	 * @param  Test_Case $test_case
	 * @return void
	 */
	public function call_tear_down_test_case_callbacks( Test_Case $test_case ) {
		$this->when_running_in_parallel(
			function () use ( $test_case ) {
				foreach ( $this->tear_down_test_case_callbacks as $callback ) {
					$this->container->call(
						$callback,
						[
							'test_case' => $test_case,
							'token'     => $this->token(),
						]
					);
				}
			}
		);
	}

	/**
	 * Get a parallel testing option.
	 *
	 * @param  string $option
	 * @return mixed
	 */
	// public function option( string $option ): mixed {
	// 	$options_resolver = $this->options_resolver ?: function ( $option ) {
	// 		$option = 'LARAVEL_PARALLEL_TESTING_' . Str::upper( $option );

	// 		return $_SERVER[ $option ] ?? false;
	// 	};

	// 	return $options_resolver( $option );
	// }

	/**
	 * Gets a unique test token.
	 *
	 * @return string|false
	 */
	public function token() {
		return $this->token_resolver
			? call_user_func( $this->token_resolver )
			: ( $_SERVER['TEST_TOKEN'] ?? false );
	}

	/**
	 * Apply the callback if tests are running in parallel.
	 *
	 * @param  callable $callback
	 * @return void
	 */
	protected function when_running_in_parallel( $callback ) {
		if ( $this->in_parallel() ) {
			$callback();
		}
	}

	/**
	 * Indicates if the current tests are been run in parallel.
	 *
	 * @return bool
	 */
	protected function in_parallel() {
		return ! empty( $this->token() );
		// return ! empty( $_SERVER['LARAVEL_PARALLEL_TESTING'] ) && $this->token();
	}
}
