<?php
/**
 * Create_Application trait file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Contracts\Exceptions\Handler as Handler_Contract;
use Mantle\Framework\Exceptions\Handler;
use Mantle\Support\Collection;

/**
 * Concern for creating the application instance.
 */
trait Create_Application {
	/**
	 * Creates the application.
	 *
	 * @return Application
	 */
	public function create_application(): \Mantle\Contracts\Application {
		$app = new Application();

		$this->resolve_application_bindings( $app );
		$this->resolve_application_config( $app );
		$this->resolve_application_core( $app );

		return $app;
	}

	/**
	 * Override application bindings, to be overridden by the child unit test.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function override_application_bindings( $app ) {
		return [];
	}

	/**
	 * Resolve application bindings.
	 *
	 * @param Application $app Application instance.
	 * @return void
	 */
	final protected function resolve_application_bindings( $app ): void {
		$app->singleton( Handler_Contract::class, Handler::class );

		foreach ( $this->override_application_bindings( $app ) as $original => $replacement ) {
			$app->bind( $original, $replacement );
		}
	}

	/**
	 * Default configuration for the test.
	 *
	 * @return array
	 */
	protected function get_application_config(): array {
		return [
			'app'        => [
				'debug'     => true,
				'providers' => [
					\Mantle\Database\Model_Service_Provider::class,
					\Mantle\Queue\Queue_Service_Provider::class,
					\Mantle\Database\Factory_Service_Provider::class,
					\Mantle\Framework\Providers\Route_Service_Provider::class,
					\Mantle\Filesystem\Filesystem_Service_Provider::class,
					\Mantle\Database\Pagination\Paginator_Service_Provider::class,
					\Mantle\Cache\Cache_Service_Provider::class,
				],
			],
			'queue'      => [
				'batch_size' => 25,
				'default'    => 'wordpress',
			],
			'logging'    => [
				'default'  => 'error_log',
				'channels' => [
					'error_log' => [
						'driver' => 'error_log',
					],
				],
			],
			'view'       => [
				'compiled' => sys_get_temp_dir(),
			],
			'filesystem' => [
				'default' => 'local',
				'disks'   => [
					'local' => [
						'driver' => 'local',
					],
					's3'    => [
						'driver' => 's3',
						'region' => 'us-west-2',
						'bucket' => '',
					],
				],
			],
			'cache'      => [
				'default' => 'wordpress',
				'stores'  => [
					'wordpress'      => [
						'driver' => 'wordpress',
					],
					'unknown-driver' => [
						'driver' => 'unknown-driver',
					],
					'array'          => [
						'driver' => 'array',
					],
					'redis'          => [
						'driver' => 'redis',
						'host'   => '127.0.0.1',
						'scheme' => 'tcp',
					],
				],
			],
		];
	}

	/**
	 * Configuration for the test.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function override_application_config( $app ): array {
		return [];
	}

	/**
	 * Resolve application core configuration.
	 *
	 * @param Application $app Application instance.
	 * @todo Allow for overriding the configuration aliases and providers easily within the unit test.
	 */
	protected function resolve_application_config( $app ) {
		$config = new Repository(
			array_merge(
				$this->get_application_config(),
				$this->override_application_config( $app )
			)
		);

		$app->instance( 'config', $config );
		$app['config']['app.providers'] = $this->resolve_application_providers( $app );
	}

	/**
	 * Resolve application core configuration.
	 *
	 * @param Application $app Application instance.
	 * @todo Allow for overriding the configuration aliases and providers easily within the unit test.
	 */
	protected function resolve_application_core( $app ) {
		$app->make( \Mantle\Framework\Bootstrap\Register_Facades::class )->bootstrap( $app );
		$app->make( \Mantle\Framework\Bootstrap\Register_Providers::class )->bootstrap( $app );
		$app->make( \Mantle\Framework\Bootstrap\Boot_Providers::class )->bootstrap( $app );
	}

	/**
	 * Get application providers.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function get_application_providers( $app ) {
		return $app['config']['app.providers'];
	}

	/**
	 * Override application aliases.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function override_application_providers( $app ) {
		return [];
	}

	/**
	 * Resolve application aliases.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	final protected function resolve_application_providers( $app ): array {
		$providers = new Collection( $this->get_application_providers( $app ) );
		$overrides = $this->override_application_providers( $app );

		if ( ! empty( $overrides ) ) {
			$providers->transform(
				static function ( $provider ) use ( $overrides ) {
					return $overrides[ $provider ] ?? $provider;
				}
			);
		}

		return $providers->all();
	}
}
