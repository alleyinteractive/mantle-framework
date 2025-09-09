<?php
/**
 * Environment attribute class file
 *
 * @package Mantle
 */

namespace Mantle\Types\Attributes;

use Attribute;
use Mantle\Types\Validator;

/**
 * Environment Attribute
 *
 * Used to define what environments a feature applies to.
 */
#[Attribute( Attribute::TARGET_CLASS )]
class Environment implements Validator {
	/**
	 * Environments to apply to.
	 *
	 * @var string[]
	 */
	public readonly array $environments;

	/**
	 * Constructor.
	 *
	 * @param string ...$environments Environments to apply to.
	 */
	public function __construct( string ...$environments ) {
		$this->environments = $environments;
	}

	/**
	 * Check if the environment is a match for the current site.
	 */
	public function validate(): bool {
		return in_array( wp_get_environment_type(), $this->environments, true );
	}
}
