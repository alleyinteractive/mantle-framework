<?php
/**
 * Console_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Console\Command;
use Mantle\Filesystem\Filesystem;
use Mantle\Framework\Console\Test_Config_Install_Command;
use Mantle\Support\Service_Provider;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Console Service Provider
 *
 * Registers core commands for the framework.
 */
class Console_Service_Provider extends Service_Provider {
	/**
	 * Register the commands from the framework.
	 *
	 * @return void
	 */
	public function register() {
		$path = MANTLE_FRAMEWORK_DIR . '/mantle/framework/console';

		if ( ! is_dir( $path ) ) {
			return;
		}

		$files = ( new Finder() )
			->in( $path )
			->files()
			->name( 'class-*-command.php' );

		$filesystem = new Filesystem();

		foreach ( $files as $file ) {
			$class = 'Mantle\\Framework\\Console'
				. str_replace( [ $path, $file->getFilename(), '/' ], [ '', '', '\\' ], $file->getRealPath() )
				. $filesystem->guess_class_name( $file->getRealPath() );

			if (
				class_exists( $class )
				&& is_subclass_of( $class, Command::class )
				&& ( new ReflectionClass( $class ) )->isInstantiable()
			) {
				$this->add_command( $class );
			}
		}

		// Remove the test config command if the test config file exists.
		if ( file_exists( Test_Config_Install_Command::get_test_config_path() ) ) {
			$this->commands = array_filter(
				$this->commands,
				fn ( Command $command ) => ! ( $command instanceof Test_Config_Install_Command ),
			);
		}
	}
}
