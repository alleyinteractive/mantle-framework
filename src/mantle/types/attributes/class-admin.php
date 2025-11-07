<?php
/**
 * Admin class file.
 *
 * @package Mantle
 */

namespace Mantle\Types\Attributes;

use Attribute;
use Mantle\Types\Validator;

/**
 * Admin Attribute
 *
 * Validates that the current request is in the WordPress admin area.
 */
#[Attribute( Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER )]
class Admin implements Validator {
	/**
	 * Validate the attribute.
	 */
	public function validate(): bool {
		return is_admin();
	}
}
