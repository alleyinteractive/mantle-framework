<?php
/**
 * View_Clear_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Filesystem\Filesystem;

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
	 * View Clear Command.
	 *
	 * @param Filesystem $files Filesystem.
	 */
	public function handle( Filesystem $files ) {
		$path = config( 'view.compiled' );

		if ( ! $path ) {
			$this->error( 'View path not found.', true );
		}

		foreach ( $this->files->glob( "{$path}/*" ) as $file ) {
			$files->delete( $file );
		}

		$this->log( 'Compiled views cleared!' );
	}
}
