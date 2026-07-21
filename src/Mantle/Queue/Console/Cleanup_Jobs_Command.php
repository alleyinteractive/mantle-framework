<?php
/**
 * Cleanup_Jobs_Commands class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Console;

use Mantle\Console\Command;
use Mantle\Database\Model\Post;
use Mantle\Queue\Jobs\Database_Job_Record;
use Mantle\Queue\Jobs\Status;

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
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{--legacy : Also delete legacy jobs stored in the mantle_queue post type.}';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Cleanup old queue jobs.';

	/**
	 * Command action.
	 */
	public function handle(): int {
		$count = 0;

		// Delete all the legacy queue jobs that were previously stored in a
		// 'mantle_queue' post type if requested.
		if ( $this->option( 'legacy', false ) ) {
			$this->info( 'Deleting legacy queue jobs...' );

			Post::for( 'mantle_queue' )->chunk_by_id( 100, function ( Post $post ) use ( &$count ): void {
				$post->delete( true );

				$count++;
			} );
		}

		Database_Job_Record::query()
			->whereIn( 'status', [ Status::RUNNING->value, Status::FAILED->value, Status::COMPLETED->value ] )
			->where_raw( 'scheduled_date_gmt', '<', now()->subSeconds( (int) $this->container['config']->get( 'queue.delete_after', 60 ) )->toDateTimeString() )
			->take( 1000 )
			->each_by_id(
				function ( Database_Job_Record $record ) use ( &$count ): void {
					if ( ! $record->is_locked() ) {
						$record->delete( true );

						$count++;
					}
				},
				100,
			);

		$this->info(
			sprintf(
				'Deleted %d %s.',
				$count,
				1 === $count ? 'job' : 'jobs',
			),
		);

		return self::SUCCESS;
	}
}
