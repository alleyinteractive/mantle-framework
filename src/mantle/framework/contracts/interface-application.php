<?php
/**
 * Application Contract interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts;

/**
 * Application Contract
 */
interface Application {
	/**
	 * Set the base path for a application.
	 *
	 * @param string $path Path to set.
	 */
	public function set_base_path( string $path );

	/**
	 * Getter for the base path.
	 *
	 * @return string
	 */
	public function get_base_path(): string;

	/**
	 * Get the path to the application configuration files.
	 *
	 * @return string
	 */
	public function get_config_path(): string;

	/**
	 * Get the Application's Environment
	 *
	 * @return string
	 */
	public function environment(): string;

	/**
	 * Check if the Application's Environment matches a list.
	 *
	 * @param string|array ...$environments Environments to check.
	 * @return bool
	 */
	public function is_environment( ...$environments ): bool;
}
