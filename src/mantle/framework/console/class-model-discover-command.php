<?php
/**
 * Model_Discover_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Model_Manifest;

/**
 * Model Discover Command
 */
class Model_Discover_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'model:discover';

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Discover models within the application for automatic registration.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Discover models within the application for automatic registration.';

	/**
	 * Manifest Object
	 *
	 * @var Model_Manifest
	 */
	protected $manifest;

	/**
	 * Constructor.
	 *
	 * @param Model_Manifest $manifest Package Manifest.
	 */
	public function __construct( Model_Manifest $manifest ) {
		$this->manifest = $manifest;
	}

	/**
	 * Discover Command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$this->log( 'Discovering models...' );

		$this->manifest->build();

		foreach ( $this->manifest->models() as $model ) {
			$this->log( 'Model discovered: ' . $this->colorize( $model, 'green' ) );
		}

		if ( empty( $this->manifest->models() ) ) {
			$this->log( $this->colorize( 'No models discovered.', 'yellow' ) );
		} else {
			$this->log( PHP_EOL . $this->colorize( 'Model manifest generated successfully.', 'green' ) );
		}
	}
}
