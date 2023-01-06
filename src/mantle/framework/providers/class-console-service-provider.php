<?php
/**
 * Console_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Console\Command;
use Mantle\Contracts\Support\Isolated_Service_Provider;
use Mantle\Support\Service_Provider;
use Mantle\Support\Traits\Loads_Classes;
use ReflectionClass;

use function Mantle\Support\Helpers\collect;

/**
 * Console Service Provider
 *
 * Registers core commands for the framework. Not designed to be located in the
 * mantle-framework/console package.
 */
class Console_Service_Provider extends Service_Provider implements Isolated_Service_Provider {
	use Loads_Classes;

	/**
	 * Register the commands from the framework.
	 *
	 * @return void
	 */
	public function register() {
		// Don't bother registering the commands if the request is not for the console.
		if ( ! $this->app->is_running_in_console() ) {
			return;
		}

		$this->add_command(
			collect( $this->classes_from_path( dirname( __DIR__, 2 ) . '/framework/console', 'Mantle\Framework\Console' ) )
				->filter(
					fn ( string $class ) => class_exists( $class )
					&& is_subclass_of( $class, Command::class )
					&& ( new ReflectionClass( $class ) )->isInstantiable()
				)
				->all()
		);
	}
}
