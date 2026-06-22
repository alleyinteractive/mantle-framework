<?php
/**
 * Interacts_With_Container trait file.
 *
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns;

use Closure;
use Mockery;

/**
 * Concern for interacting with the container for helpful testing.
 */
trait Interacts_With_Container {

	/**
	 * Register an instance of an object in the container.
	 *
	 * @param  string $abstract Abstract to swap.
	 * @param  object $instance Instance to use.
	 * @return object
	 */
	protected function swap( $abstract, $instance ) {
		return $this->instance( $abstract, $instance );
	}

	/**
	 * Register an instance of an object in the container.
	 *
	 * @throws \RuntimeException If the application container is not available.
	 *
	 * @param  string $abstract Abstract to swap.
	 * @param  object $instance Instance to use.
	 * @return object
	 */
	protected function instance( $abstract, $instance ) {
		if ( ! $this->app ) {
			throw new \RuntimeException( 'The application container is not available.' );
		}

		$this->app->instance( $abstract, $instance );

		return $instance;
	}

	/**
	 * Mock an instance of an object in the container.
	 *
	 * @param  string        $abstract Abstract to swap.
	 * @param  \Closure|null $mock Mock to use.
	 */
	protected function mock( $abstract, ?Closure $mock = null ): \Mockery\MockInterface {
		$mock_instance = Mockery::mock( ...array_filter( func_get_args() ) );
		$this->instance( $abstract, $mock_instance );
		return $mock_instance;
	}

	/**
	 * Mock a partial instance of an object in the container.
	 *
	 * @param  string        $abstract Abstract to swap.
	 * @param  \Closure|null $mock Mock to use.
	 */
	protected function partial_mock( $abstract, ?Closure $mock = null ): \Mockery\LegacyMockInterface {
		$mock_instance = Mockery::mock( ...array_filter( func_get_args() ) )->makePartial();
		$this->instance( $abstract, $mock_instance );
		return $mock_instance;
	}

	/**
	 * Spy an instance of an object in the container.
	 *
	 * @param  string        $abstract Abstract to swap.
	 * @param  \Closure|null $mock Mock to use.
	 */
	protected function spy( $abstract, ?Closure $mock = null ): \Mockery\MockInterface {
		$mock_instance = Mockery::spy( ...array_filter( func_get_args() ) );
		$this->instance( $abstract, $mock_instance );
		return $mock_instance;
	}
}
