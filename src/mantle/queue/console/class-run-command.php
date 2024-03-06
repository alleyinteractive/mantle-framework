<?php
/**
 * Run_Command class file.
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
 * Queue Run Command
 */
class Run_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:run';
	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Run items from a queue.';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{queue=default} {--count}';

	/**
	 * Queue Run Command.
	 */
	public function handle(): void {
		$queue = $this->argument( 'queue' );

		// Register the event listeners to pipe the events back to the console.
		$this->container['events']->listen(
			Run_Start::class,
			function( Run_Start $event ) {
				$this->log( 'Run started: ' . $event->queue );
			}
		);

		$this->container['events']->listen(
			Job_Processing::class,
			function( Job_Processing $job ) {
				$this->log( 'Queue item started: ' . $job->get_id() );
			}
		);

		$this->container['events']->listen(
			Job_Processed::class,
			function( Job_Processed $job ) {
				$this->log( 'Queue item complete: ' . $job->get_id() );
			}
		);

		$this->container['events']->listen(
			Run_Complete::class,
			function( Run_Complete $event ) {
				$this->log( 'Run complete: ' . $event->queue );
			}
		);

		$this->container['queue.worker']->run(
			(int) $this->option( 'count', (int) ( $this->container['config']['queue.batch_size'] ?? 1 ) ),
			$queue
		);
	}
}
