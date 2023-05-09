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
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Create a wp-test-config.php file for local development.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		// Hide the command if the test configuration is already installed.
		if ( file_exists( static::get_test_config_path() ) ) {
			$this->setHidden( true );
		}
	}

	/**
	 * Test Config Install Command.
	 */
	public function handle() {
		$path = static::get_test_config_path();

		if ( file_exists( $path ) ) {
			$this->error( __( 'Test configuration already exists!', 'mantle' ) );
			return Command::FAILURE;
		}

		if ( ! copy( __DIR__ . '/../../../mantle/testing/wp-tests-config-sample.php', $path ) ) {
			$this->error( __( 'Error copying configuration file.', 'mantle' ) );
			return Command::FAILURE;
		}

		$this->log(
			sprintf(
				/* translators: 1: config file path */
				__( 'Configuration copied to %s. Update it to reference your proper database credentials.', 'mantle' ),
				$path,
			),
		);

		return Command::SUCCESS;
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
