<?php
/**
 * Cleanup_Jobs_Commands class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Console;

use Mantle\Console\Command;
use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Queue_Record;

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
		$count = 0;

		Queue_Record::query()
			->whereStatus( [ Post_Status::RUNNING->value, Post_Status::FAILED->value, Post_Status::COMPLETED->value ] )
			->olderThan( now()->subSeconds( (int) $this->container['config']->get( 'queue.delete_after', 60 ) ) )
			->take( -1 )
			->each_by_id(
				function ( Queue_Record $record ) use ( &$count ) {
					if ( ! $record->is_locked() ) {
						$record->delete( true );

						$count++;
					}
				},
				100,
			);

		$this->info( sprintf( 'Deleted %d jobs.', $count ) );
	}
}
