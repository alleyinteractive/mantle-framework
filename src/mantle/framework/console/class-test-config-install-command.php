<?php
/**
 * Test_Config_Install class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Filesystem\Filesystem;

/**
 * Command to install the test configuration file
 */
class Test_Config_Install_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'test-config';

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Create a wp-test-config.php file for local development.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Create a wp-test-config.php file for local development.';

	/**
	 * Filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Constructor.
	 *
	 * @param Filesystem $files Filesystem.
	 */
	public function __construct( Filesystem $files ) {
		$this->files = $files;
	}

	/**
	 * Test Config Install Command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$path = static::get_test_config_path();

		if ( file_exists( $path ) ) {
			$this->error( __( 'Test configuration already exists!', 'mantle' ), true );
		}

		if ( ! copy( __DIR__ . '/../../../mantle/testing/wp-tests-config-sample.php', $path ) ) {
			$this->error( __( 'Error copying configuration file.', 'mantle' ), true );
		}

		$this->log( __( 'Configuration copied! Update it to reference your proper database credentials.', 'mantle' ) );
	}

	/**
	 * Retrieve the test config path.
	 *
	 * @return string
	 */
	public static function get_test_config_path(): string {
		return ABSPATH . '/wp-tests-config.php';
	}
}
