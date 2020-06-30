<?php
/**
 * Registrable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Database;

/**
 * Registrable Fields Model Interface
 *
 * Provides methods to register a model's fields automatically.
 */
interface Registrable_Fields {
	/**
	 * Method to register the model's fields.
	 */
	public static function register_fields();
}
