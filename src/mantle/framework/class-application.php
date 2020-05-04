<?php
/**
 * Application class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework;

use Mantle\Framework\Contracts\Application as Application_Contract;

/**
 * Mantle Application
 */
class Application extends Container\Container implements Application_Contract {
	/**
	 * Indicates if the application has been bootstrapped before.
	 *
	 * @var bool
	 */
	protected $has_been_bootstrapped = false;

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * All of the registered service providers.
	 *
	 * @var ServiceProvider[]
	 */
	protected $service_providers = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_base_bindings();
	}

	/**
	 * Determine if the application has been bootstrapped before.
	 *
	 * @return bool
	 */
	public function has_been_bootstrapped(): bool {
		return (bool) $this->has_been_bootstrapped;
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function register_base_bindings() {
		static::set_instance( $this );

		$this->instance( 'app', $this );
		$this->instance( Container\Container::class, $this );
		$this->instance( static::class, $this );
	}

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * Bootstrap classes should implement `Mantle\Framework\Contracts\Bootstrapable`.
	 *
	 * @param string[] $bootstrappers Class names of packages to boot.
	 */
	public function bootstrap_with( array $bootstrappers ) {
		$this->has_been_bootstrapped = true;

		foreach ( $bootstrappers as $bootstrapper ) {
			$this->make( $bootstrapper )->bootstrap( $this );
		}
	}

	/**
	 * Register all of the configured providers.
	 */
	public function register_configured_providers() {
		// todo: replace with config class file.
		$config = include MANTLE_BASE_DIR . '/config/app.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

		array_map( [ $this, 'register' ], $config['providers'] );
	}

	/**
	 * Get all service providers.
	 *
	 * @return Service_Provider[]
	 */
	public function get_providers(): array {
		return $this->service_providers;
	}

	/**
	 * Register a Service Provider
	 *
	 * @param Service_Provider|string $provider Provider instance or class name to register.
	 * @return Application
	 */
	public function register( $provider ): Application {
		$provider_name = is_string( $provider ) ? $provider : get_class( $provider );

		if ( ! empty( $this->service_providers[ $provider_name ] ) ) {
			var_dump('already registered');
			return $this;
		}

		if ( is_string( $provider ) ) {
			$provider = new $provider( $this );
		}

		if ( ! ( $provider instanceof Service_Provider ) ) {
			\wp_die( 'Provider is not instance of Service_Provider: ' . $provider_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$provider->register();
		$this->service_providers[ $provider_name ] = $provider;
		return $this;
	}

	/**
	 * Determine if the application has booted.
	 *
	 * @return bool
	 */
	public function is_booted() {
		return $this->booted;
	}

	/**
	 * Boot the application's service providers.
	 *
	 * @return Application
	 */
	public function boot(): Application {
		if ( $this->is_booted() ) {
			return $this;
		}

		foreach ( $this->service_providers as $provider ) {
			$provider->boot();
		}

		$this->booted = true;
		return $this;
	}
}
