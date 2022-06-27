<?php
/**
 * Factory_Service_Provider class file.
 *
 * @package Mantle
 */

// phpcs:ignoreFile: WordPressVIPMinimum.Variables.VariableAnalysis.StaticInsideClosure

namespace Mantle\Database;

use \Faker\Factory;
use \Faker\Generator as FakerGenerator;
use Mantle\Database\Factory\Factory as MantleFactory;
use Mantle\Support\Service_Provider;

/**
 * Database Factory
 */
class Factory_Service_Provider extends Service_Provider {
	/**
	 * The array of resolved Faker instances.
	 *
	 * @var array
	 */
	protected static $fakers = [];

	/**
	 * Register any application services.
	 */
	public function register() {
		$this->add_command( Console\Seed_Command::class );

		$this->register_mantle_factory();
	}

	/**
	 * Register the Mantle factory instance in the container.
	 *
	 * @return void
	 */
	protected function register_mantle_factory() {
		$this->app->singleton(
			FakerGenerator::class,
			function ( $app, $parameters ) {
				$locale = config( 'app.faker_locale', Factory::DEFAULT_LOCALE );

				if ( ! isset( static::$fakers[ $locale ] ) ) {
					static::$fakers[ $locale ] = Factory::create();
				}

				static::$fakers[ $locale ]->unique( true );

				return static::$fakers[ $locale ];
			}
		);

		$this->app->singleton(
			MantleFactory::class,
			function ( $app ) {
				return MantleFactory::construct(
					$app->make(
						FakerGenerator::class
					),
					$app->get_base_path() . '/database/factories'
				);
			}
		);
	}
}
