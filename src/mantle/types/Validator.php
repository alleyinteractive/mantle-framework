<?php
/**
 * Validator interface file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Types;

/**
 * Validator
 *
 * Used to define attributes that require validation. Implementations should
 * provide logic to determine if the attribute meets specific criteria.
 */
interface Validator {
	/**
	 * Method invoked to determine if the feature is valid and should be booted.
	 */
	public function validate(): bool;
}
