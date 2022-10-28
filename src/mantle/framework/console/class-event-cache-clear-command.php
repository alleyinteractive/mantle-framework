<?php
/**
 * Config_Clear_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use LogicException;
use Mantle\Contracts\Application;
use Mantle\Console\Command;
use Mantle\Filesystem\Filesystem;

/**
 * Clear the event cache.
 */
class Event_Cache_Clear_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'event:clear';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Clear the local event cache.';

	/**
	 * Flush Mantle's local cache.
	 *
	 * @param Filesystem $filesystem Filesystem instance.
	 * @throws LogicException Thrown on error writing config file.
	 */
	public function handle( Filesystem $filesystem ) {
		$filesystem->delete(
			$this->container->get_cached_events_path(),
		);

		$this->log( 'Event cache cached successfully!' );
	}
}
