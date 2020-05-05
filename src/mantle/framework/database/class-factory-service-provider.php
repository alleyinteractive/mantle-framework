<?php
/**
 * Factory_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database;

use Mantle\Framework\Service_Provider;

/**
 * Database Factory
 *
 * @link https://laravel.com/docs/7.x/seeding#using-model-factories
 */
class Factory_Service_Provider extends Service_Provider {
	/**
	 * Register any application services.
	 */
	public function register() {
		$this->add_command( Console\Seed_Command::class );
	}
}
