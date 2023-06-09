<?php
/**
 * Has_Service_Providers trait file.
 *
 * @package Mantle
 */

namespace Mantle\Application\Concerns;

use InvalidArgumentException;
use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Events\Event_Service_Provider;
use Mantle\Framework\Manifest\Package_Manifest;
use Mantle\Framework\Providers\Console_Service_Provider;
use Mantle\Framework\Providers\Routing_Service_Provider;
use Mantle\Log\Log_Service_Provider;
use Mantle\Support\Service_Provider;
use Mantle\View\View_Service_Provider;

use function Mantle\Support\Helpers\collect;

/**
 * Trait to manage service providers for the application.
 *
 * @mixin \Mantle\Application\Application
 */
trait Manages_Service_Providers {
	/**
	 * All of the registered service providers.
	 *
	 * @var Service_Provider[]
	 */
	protected $service_providers = [];

	/**
	 * Register the base service providers.
	 */
	protected function register_base_service_providers() {
		$this->register( Console_Service_Provider::class );
		$this->register( Event_Service_Provider::class );
		$this->register( Log_Service_Provider::class );
		$this->register( View_Service_Provider::class );
		$this->register( Routing_Service_Provider::class );
	}

	/**
	 * Register all of the configured providers.
	 */
	public function register_configured_providers() {
		// Get providers from the application config.
		$providers = collect( $this->make( 'config' )->get( 'app.providers', [] ) );

		// Include providers from the package manifest.
		$providers->push( ...$this->make( Package_Manifest::class )->providers() );

		// Only register service providers that implement Isolated_Service_Provider
		// when in isolation mode.
		if ( $this->is_running_in_console_isolation() ) {
			$providers = $providers->filter(
				fn ( string $provider ) => in_array(
					Isolated_Service_Provider::class,
					class_implements( $provider ),
					true,
				)
			);
		}

		$providers->each( [ $this, 'register' ] );
	}

	/**
	 * Get an instance of a service provider.
	 *
	 * @param class-string<Service_Provider> $name Provider class name.
	 * @return Service_Provider|null
	 */
	public function get_provider( string $name ): ?Service_Provider {
		return collect( $this->get_providers() )->first(
			fn( Service_Provider $provider ) => $provider instanceof $name,
		);
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
	 * @throws InvalidArgumentException If the provider is not an instance of Service_Provider.
	 *
	 * @param Service_Provider|class-string<Service_Provider> $provider Provider instance or class name to register.
	 * @return static
	 */
	public function register( Service_Provider|string $provider ): static {
		$provider_name = is_string( $provider ) ? $provider : get_class( $provider );

		if ( ! empty( $this->service_providers[ $provider_name ] ) ) {
			return $this;
		}

		if ( is_string( $provider ) ) {
			$provider = new $provider( $this );
		}

		if ( ! ( $provider instanceof Service_Provider ) ) {
			throw new InvalidArgumentException( "Provider is not instance of Service_Provider: {$provider_name}" );
		}

		$provider->register();

		$this->service_providers[ $provider_name ] = $provider;

		return $this;
	}
}
