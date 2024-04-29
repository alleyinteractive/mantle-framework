<?php
/**
 * Register_Providers class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Mantle\Application\Application;
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
}
