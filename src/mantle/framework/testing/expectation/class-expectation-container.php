<?php
namespace Mantle\Framework\Testing\Expectation;

use Mantle\Framework\Support\Collection;

class Expectation_Container {
	public const ACTION_ADDED = 'added';
	public const ACTION_APPLIED = 'applied';

	/**
	 * Expectations
	 *
	 * @var Collection
	 */
	protected $expectations;

	public function __construct() {
		$this->expectations = new Collection();
	}

	/**
	 * Create an expectation.
	 *
	 * @param string $hook
	 * @param array $args
	 * @return Expectation
	 */
	public function add_applied( string $hook, ...$args ): Expectation {
		$expectation = new Expectation( static::ACTION_APPLIED, $hook, $args );
		$this->expectations->push( $expectation );
		return $expectation;
	}

	/**
	 * Validate the expectations in the container.
	 */
	public function validate(): void {
		$this->expectations->each->validate();
	}
}
