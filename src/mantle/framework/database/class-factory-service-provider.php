<?php
/**
 * Factory_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database;

use \Faker\Factory;
use \Faker\Generator as FakerGenerator;
use Mantle\Framework\Database\Factory\Factory as MantleFactory;
use Mantle\Framework\Service_Provider;

/**
 * Database Factory
 *
 * @link https://laravel.com/docs/7.x/seeding#using-model-factories
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

		$this->registerMantleFactory();
	}

	/**
	 * Register the Eloquent factory instance in the container.
	 *
	 * @return void
	 */
	protected function registerMantleFactory()
	{
//		$this->app->singleton(Generator::class, function ($app, $parameters) {
////			$locale = $parameters['locale'] ?? $app['config']->get('app.faker_locale', 'en_US');
//			$locale = 'en_US';
//
//			if (! isset(static::$fakers[$locale])) {
//				static::$fakers[$locale] = Factory::create();
//			}
//
//			static::$fakers[$locale]->unique(true);
//
//			return static::$fakers[$locale];
//		});

		$this->app->singleton(MantleFactory::class, function ($app) {
			return MantleFactory::construct(
				$app->make(FakerGenerator::class), $app->get_base_path() . '/database/factories'
			);
		});
	}
}
