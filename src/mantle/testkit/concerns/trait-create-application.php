<?php
/**
 * Create_Application trait file
 *
 * @package Mantle
 */

namespace Mantle\Testkit\Concerns;

use Mantle\Testkit\Application;
use Mantle\Contracts\Exceptions\Handler as Handler_Contract;
use Mantle\Framework\Bootloader;
use Mantle\Http\Request;
use Mantle\Http\Routing\Url_Generator;
use Mantle\Testkit\Exception_Handler;
use Symfony\Component\Routing\RouteCollection;

/**
 * Concern for creating the application instance for Mantle TestKit.
 *
 * This trait is used to create a semi-isolated instance of the Mantle
 * Application that doesn't have all the bells and whistles of a full Mantle
 * Application. Notably, the service providers are never registered or booted
 * here.
 */
trait Create_Application {
	/**
	 * Creates the application.
	 *
	 * @return Application
	 */
	public function create_application(): \Mantle\Contracts\Application {
		Bootloader::create( $app = new Application() )
			->with_config(
				[
					'view' => [
						'compiled' => sys_get_temp_dir(),
					],
					...$this->override_application_config( $app ),
				]
			);

			$this->resolve_application_core( $app );
			$this->resolve_application_bindings( $app );

		return $app;
	}

	/**
	 * Resolve application core configuration.
	 *
	 * @todo Review if we can add Register_Providers bootstrap here.
	 *
	 * @param Application $app Application instance.
	 */
	protected function resolve_application_core( $app ): void {
		$app->make( \Mantle\Framework\Bootstrap\Load_Configuration::class )->bootstrap( $app );
		$app->make( \Mantle\Framework\Bootstrap\Boot_Providers::class )->bootstrap( $app );
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
	 */
	final protected function resolve_application_bindings( $app ): void {
		// Register the TestKit exception handler.
		$app->singleton( Handler_Contract::class, Exception_Handler::class );

		foreach ( $this->override_application_bindings( $app ) as $original => $replacement ) {
			$app->bind( $original, $replacement );
		}

		$app->singleton_if(
			'url',
			fn ( $app ) => new Url_Generator(
				$app->get_root_url(),
				new RouteCollection(),
				$app['request'] ?? new Request(),
			),
		);
	}

	/**
	 * Configuration for the test.
	 *
	 * @param Application $app Application instance.
	 */
	protected function override_application_config( $app ): array {
		return [];
	}
}
