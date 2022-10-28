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
use Mantle\Framework\Events\Events_Manifest;

/**
 * Store the event cache.
 */
class Event_Cache_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'event:cache';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Cache the currently registered events.';

	/**
	 * Flush Mantle's local cache.
	 *
	 * @throws LogicException Thrown on error writing config file.
	 */
	public function handle() {
		$this->call( 'mantle event:clear' );

		$this->container[ Events_Manifest::class ]->build();

		$this->log( 'Events cached cached successfully!' );
	}
}
