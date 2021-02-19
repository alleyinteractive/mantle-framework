<?php
/**
 * Package_Discover_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Console;

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
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Discover package dependencies from Composer';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Discover package dependencies from Composer';

	/**
	 * Manifest Object
	 *
	 * @var Package_Manifest
	 */
	protected $manifest;

	/**
	 * Constructor.
	 *
	 * @param Package_Manifest $manifest Package Manifest.
	 */
	public function __construct( Package_Manifest $manifest ) {
		$this->manifest = $manifest;
	}

	/**
	 * Discover Command.
	 *
	 * @todo Replace with a filesystem abstraction.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$this->log( 'Discovering...' );

		$this->manifest->build();

		$this->log( 'Package manifest generated successfully.' );
	}
}
