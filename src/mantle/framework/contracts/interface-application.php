<?php
/**
 * Application Contract interface file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Contracts;

use Mantle\Framework\Contracts\Kernel as Kernel_Contract;
use Mantle\Framework\Service_Provider;

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
	 * Set the root URL of the application.
	 *
	 * @param string $url Root URL to set.
	 */
	public function set_root_url( string $url );

	/**
	 * Getter for the root URL.
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	public function get_root_url( string $path = '' ): string;

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

	/**
	 * Determine if the application has booted.
	 *
	 * @return bool
	 */
	public function is_booted(): bool;

	/**
	 * Boot the application's service providers.
	 */
	public function boot();

	/**
	 * Register a new boot listener.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function booting( $callback );

	/**
	 * Register a new "booted" listener.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function booted( $callback );

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * Bootstrap classes should implement `Mantle\Framework\Contracts\Bootstrapable`.
	 *
	 * @param string[]        $bootstrappers Class names of packages to boot.
	 * @param Kernel_Contract $kernel Kernel instance.
	 */
	public function bootstrap_with( array $bootstrappers, Kernel_Contract $kernel );

	/**
	 * Get an instance of a service provider.
	 *
	 * @param string $name Provider class name.
	 * @return Service_Provider|null
	 */
	public function get_provider( string $name ): ?Service_Provider;

	/**
	 * Get all service providers.
	 *
	 * @return Service_Provider[]
	 */
	public function get_providers(): array;
}
