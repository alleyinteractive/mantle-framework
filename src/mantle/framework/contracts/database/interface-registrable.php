<?php
/**
 * Registrable interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts\Database;

/**
 * Registrable Model Interface
 *
 * Provides methods to register a model with WordPress either through a post type or
 * a custom taxonomy.
 */
interface Registrable {
	/**
	 * Method to register the model.
	 */
	public static function register();

	/**
	 * Registration name for the model (post type, taxonomy name, etc.)
	 *
	 * @return string
	 */
	public static function get_registration_name(): string;

	/**
	 * Arguments to register the model with.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array;
}
