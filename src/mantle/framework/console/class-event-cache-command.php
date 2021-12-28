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
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Cache the currently registered events.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Cache the currently registered events.';

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Command synopsis.
	 *
	 * @var array
	 */
	protected $synopsis = '';

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 * @param Filesystem  $filesystem Filesystem instance.
	 */
	public function __construct( Application $app, Filesystem $filesystem ) {
		$this->app   = $app;
		$this->files = $filesystem;
	}

	/**
	 * Flush Mantle's local cache.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 *
	 * @throws LogicException Thrown on error writing config file.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		$this->call( 'mantle event:clear' );

		$this->app[ Events_Manifest::class ]->build();

		$this->log( 'Events cached cached successfully!' );
	}
}
