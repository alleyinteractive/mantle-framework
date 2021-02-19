<?php
/**
 * View_Clear_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Mantle\Framework\Filesystem\Filesystem;
use RuntimeException;

/**
 * Command to clear the compiled views.
 */
class View_Clear_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'view:clear';

	/**
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Clear the compiled views.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Clear the compiled views.';

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
	 * View Clear Command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$path = config( 'view.compiled' );

		if ( ! $path ) {
			$this->error( 'View path not found.', true );
		}

		foreach ( $this->files->glob( "{$path}/*" ) as $file ) {
			$this->files->delete( $file );
		}

		$this->log( 'Compiled views cleared!' );
	}
}
