<?php
/**
 * Package_Discover_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Package_Manifest;

/**
 * Package Discover Command
 */
class Package_Discover_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'package:discover';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Discover package dependencies from Composer';

	/**
	 * Discover Command.
	 *
	 * @todo Replace with a filesystem abstraction.
	 *
	 * @param Package_Manifest $manifest Package Manifest.
	 */
	public function handle( Package_Manifest $manifest ) {
		$this->manifest = $manifest;

		$this->log( 'Discovering packages...' );

		$this->manifest->build();

		foreach ( array_keys( $this->manifest->get_manifest() ) as $package ) {
			$this->log( 'Discovered Package: ' . $this->colorize( $package, 'green' ) );
		}

		if ( empty( $this->manifest->get_manifest() ) ) {
			$this->log( $this->colorize( 'No packages discovered.', 'yellow' ) );
		} else {
			$this->log( PHP_EOL . $this->colorize( 'Package manifest generated successfully.', 'green' ) );
		}
	}
}
