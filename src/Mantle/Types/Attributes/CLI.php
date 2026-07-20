<?php
/**
 * CLI attribute class file
 *
 * @package Mantle
 */

namespace Mantle\Types\Attributes;

use Attribute;
use Mantle\Types\Validator;

/**
 * CLI Attribute
 *
 * Used to define a feature that should only be loaded in WP-CLI context.
 */
#[Attribute( Attribute::TARGET_CLASS )]
class CLI implements Validator {
	/**
	 * Check if the environment is a match for the current site.
	 */
	public function validate(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}
}
