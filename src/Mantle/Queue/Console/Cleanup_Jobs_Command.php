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

			// The legacy queue_* post statuses are no longer registered, so only "any" matches them.
			Post::for( 'mantle_queue' )
				->where( 'post_status', 'any' )
				->each_by_id(
					function ( Post $post ) use ( &$count ): void {
						$post->delete( true );

						$count++;
					},
					100,
				);
		}

		Database_Job_Record::query()
			->whereIn( 'status', [ Status::FAILED->value, Status::COMPLETED->value ] )
			->where_raw( 'scheduled_date_gmt', '<', now( 'UTC' )->subSeconds( (int) $this->container['config']->get( 'queue.delete_after', 60 ) )->toDateTimeString() )
			->each_by_id(
				function ( Database_Job_Record $record ) use ( &$count ): void {
					$record->delete( true );

					$count++;
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
