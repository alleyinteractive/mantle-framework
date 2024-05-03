<?php
/**
 * Register_Providers class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Application\App_Service_Provider as Framework_App_Service_Provider;
use Mantle\Application\Application;
use Mantle\Support\Service_Provider as Base_Service_Provider;
use Mantle\Contracts\Bootstrapable as Bootstrapable_Contract;
use RuntimeException;

use function Mantle\Support\Helpers\collect;

/**
 * Register the Service Providers with the Application from the config.
 */
class Register_Providers implements Bootstrapable_Contract {
	/**
	 * Additional service providers to register from the bootloader.
	 *
	 * @var array<class-string<\Mantle\Support\Service_Provider>>
	 */
	protected static $merge = [];

	/**
	 * Merge additional service providers to the list of providers.
	 *
	 * @param array<class-string<\Mantle\Support\Service_Provider>> $providers List of service providers.
	 */
	public static function merge( array $providers ): void {
		static::$merge = array_merge( static::$merge, $providers );
	}

	/**
	 * Clear the list of merged providers.
	 */
	public static function flush(): void {
		static::$merge = [];
	}

	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ): void {
		$this->merge_additional_providers( $app );
		$this->purge_framework_providers( $app );

		$app->register_configured_providers();
	}

	/**
	 * Merge the additional providers into the application.
	 *
	 * @throws RuntimeException If the config repository is not available.
	 *
	 * @param Application $app Application instance.
	 */
	protected function merge_additional_providers( Application $app ): void {
		if ( empty( static::$merge ) ) {
			return;
		}

		if ( ! isset( $app['config'] ) ) {
			throw new RuntimeException( 'The config repository is not available.' );
		}

		$config = $app->make( 'config' );

		$config->set(
			'app.providers',
			collect( $config->get( 'app.providers' ) )->merge( static::$merge )->unique()->all(),
		);
	}

	/**
	 * Purge framework providers from the configuration if they are being
	 * overridden by the application.
	 *
	 * Ensure that framework providers such as Mantle\Application\App_Service_Provider
	 * are removed if they are extended and implemented by the application.
	 *
	 * @param Application $app Application instance.
	 */
	protected function purge_framework_providers( Application $app ): void {
		$config    = $app->make( 'config' );
		$providers = collect( $config->get( 'app.providers', [] ) );

		// Determine the parent classes for each application provider.
		$application_parents = $providers
			// Filter out all framework providers.
			->filter(
				fn ( string $provider ) => ! str_starts_with( $provider, 'Mantle\\' ),
			)
			// Map the parent classes for each provider to properly remove duplicates.
			->map_with_keys(
				fn ( string $provider ) => [
					$provider => collect( class_parents( $provider ) )->filter( fn ( $parent ) => Base_Service_Provider::class !== $parent )->all(),
				],
			)
			->flatten()
			->values()
			->unique();

		// Remove the framework providers that are extended by the application.
		$config->set( 'app.providers', $providers->diff( $application_parents )->values()->all() );
	}
}
