<?php
/**
 * Model_Discover_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Manifest\Model_Manifest;

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
	 * Discover Command.
	 *
	 * @param Model_Manifest $manifest Package Manifest.
	 */
	public function handle( Model_Manifest $manifest ) {
		$this->manifest = $manifest;

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
