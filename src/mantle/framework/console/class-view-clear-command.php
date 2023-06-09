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
			$this->error( 'View path not found.' );
			return Command::FAILURE;
		}

		foreach ( $files->glob( "{$path}/*" ) as $file ) {
			$files->delete( $file );
		}

		$this->success( 'Compiled views cleared.' );

		return Command::SUCCESS;
	}
}
