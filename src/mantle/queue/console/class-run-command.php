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
	 * Command Short Description.
	 *
	 * @var string
	 */
	protected $short_description = 'Run items from a queue.';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Run items from a queue.';

	/**
	 * Command synopsis.
	 *
	 * @var string|array
	 */
	protected $synopsis = [
		[
			'description' => 'Queue name.',
			'name'        => 'queue',
			'optional'    => false,
			'type'        => 'positional',
		],
		[
			'description' => 'Batch size, defaults to application default.',
			'name'        => 'count',
			'optional'    => true,
			'type'        => 'flag',
		],
	];

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	protected Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Queue Run Command.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {
		[ $queue ] = $args;

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
			(int) $this->flag( 'count', (int) $this->container['config']['queue.batch_size'] ?? 1 ),
			$queue
		);
	}
}
