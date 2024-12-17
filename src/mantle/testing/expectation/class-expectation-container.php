<?php
/**
 * Expectation_Container class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Expectation;

use Mantle\Support\Collection;

use function Mantle\Support\Helpers\tap;

/**
 * Container for the expectation checking.
 */
class Expectation_Container {
	/**
	 * Expectations
	 *
	 * @var Collection<Expectation>
	 */
	protected Collection $expectations;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->expectations = new Collection();
	}

	/**
	 * Create an expectation for checking if a hook was fired.
	 *
	 * @param string $hook Hook to check.
	 */
	public function add_applied( string $hook ): Expectation {
		return tap(
			new Expectation( Action::APPLIED, $hook ),
			fn ( Expectation $expectation ) => $this->expectations->push( $expectation ),
		);
	}

	/**
	 * Create an expectation for checking if a hook was added.
	 *
	 * @param string   $hook Hook to check.
	 * @param callable $callback Callback for the hook, optional.
	 */
	public function add_added( string $hook, callable $callback = null ): Expectation {
		return tap(
			new Expectation( Action::ADDED, $hook ),
			function ( Expectation $expectation ) use ( $callback ) {
				if ( $callback ) {
					$expectation->with( $callback );
				}

				$this->expectations->push( $expectation );
			},
		);
	}

	/**
	 * Validate the expectations in the container.
	 */
	public function tear_down(): void {
		$this->expectations->each(
			fn ( Expectation $expectation ) => $expectation->validate(),
		);
	}
}
