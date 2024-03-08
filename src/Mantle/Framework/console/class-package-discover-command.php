<?php
/**
 * Package_Discover_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Manifest\Package_Manifest;

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
	 * Package Manifest.
	 */
	protected Package_Manifest $manifest;

	/**
	 * Discover Command.
	 *
	 * @todo Replace with a filesystem abstraction.
	 *
	 * @param Package_Manifest $manifest Package Manifest.
	 */
	public function handle( Package_Manifest $manifest ): void {
		$this->manifest = $manifest;

		$this->line( 'Discovering packages...' );

		$this->manifest->build();

		foreach ( array_keys( $this->manifest->get_manifest() ) as $package ) {
			$this->line( 'Discovered Package: ' . $this->colorize( $package, 'green' ) );
		}

		if ( empty( $this->manifest->get_manifest() ) ) {
			$this->line( $this->colorize( 'No packages discovered.', 'yellow' ) );
		} else {
			$this->line( PHP_EOL . $this->colorize( 'Package manifest generated successfully.', 'green' ) );
		}
	}
}
