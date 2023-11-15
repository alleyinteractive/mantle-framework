<?php
/**
 * Cleanup_Jobs_Commands class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Console;

use Mantle\Console\Command;
use Mantle\Contracts\Container;
use Mantle\Queue\Events\Job_Processed;
use Mantle\Queue\Events\Job_Processing;
use Mantle\Queue\Events\Run_Complete;
use Mantle\Queue\Events\Run_Start;

/**
 * Queue Cleanup Command
 */
class Cleanup_Jobs_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:cleanup';
	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Cleanup old queue jobs.';

	/**
	 * Command action.
	 */
	public function handle() {

	}
}
