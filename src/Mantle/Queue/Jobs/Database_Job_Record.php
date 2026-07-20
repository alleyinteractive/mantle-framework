<?php
/**
 * Queue_ class file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Jobs;

use Carbon\Carbon;
use Mantle\Database\Model\Database_Table_Model;
use Mantle\Queue\Database_Event;
use RuntimeException;

/**
 * Queue Job Record
 *
 * Used to store the queued jobs as posts in the database. Post statuses are
 * used to track the job state and the post meta is used to store the job
 * details/lock.
 *
 * @property int $id The unique identifier for the job.
 * @property string $queue The name of the queue the job belongs to.
 * @property string $created_date_gmt The date the job was created in GMT.
 * @property string $scheduled_date_gmt The date the job is scheduled to run in GMT.
 * @property mixed $action The action to perform when the job is processed.
 * @property string|null $unique_id An optional unique identifier for the job.
 * @property int $attempts The number of attempts made to process the job.
 * @property string $status The status of the job.
 * @property string|null $last_attempt_gmt The date of the last attempt to process the job in GMT.
 * @property string $available_at_gmt The date the job is available to be processed in GMT.
 * @property string $log The log of events for the job.
 */
class Database_Job_Record extends Database_Table_Model {
	/**
	 * Casts for the model properties.
	 *
	 * @var array<string, string>
	 */
	protected array $casts = [
		'id' => 'int',
	];

	/**
	 * Retrieve the table name for the model.
	 *
	 * @throws RuntimeException If the table name is not set.
	 */
	#[\Override]
	public static function get_table_name(): string {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		if ( ! isset( $wpdb->mantle_queue ) ) {
			throw new RuntimeException(
				'Queue_Record::get_table_name() requires the database tables to be created.',
			);
		}

		return $wpdb->mantle_queue;
	}

	/**
	 * Retrieve the database job instance for the record.
	 */
	public function job(): Database_Job {
		return new Database_Job( $this );
	}

	/**
	 * Check if the queue job is locked.
	 */
	public function is_locked(): bool {
		return Status::RUNNING->value === $this->status && now()->isBefore( $this->get_lock_until() );
	}

	/**
	 * Get the lock end time.
	 */
	public function get_lock_until(): Carbon {
		return Carbon::parse( $this->available_at_gmt );
	}

	/**
	 * Set the lock end time and update the job status to running.
	 *
	 * @param Carbon $lock_until The time until which the job should be locked.
	 */
	public function set_lock_until( Carbon|int $lock_until ): void {
		if ( is_int( $lock_until ) ) {
			$lock_until = Carbon::now()->addSeconds( $lock_until );
		}

		$this->save( [
			'available_at_gmt' => $lock_until->toDateTimeString(),
			'status'           => Status::RUNNING->value,
		] );
	}

	/**
	 * Log an event for the job.
	 *
	 * @param Database_Event $event The event to log.
	 * @param array<mixed>   $payload The event payload.
	 */
	public function log_event( Database_Event $event, array $payload = [] ): void {
		$log = json_decode( $this->log ?? '[]', true );

		$log[] = [
			'event'   => $event->value,
			'payload' => $payload,
			'time'    => \time(),
		];

		$this->save( [ 'log' => json_encode( $log ) ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}
