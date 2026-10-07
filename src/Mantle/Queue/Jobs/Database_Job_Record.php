<?php
/**
 * Database_Job_Record class file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Jobs;

use Carbon\Carbon;
use Mantle\Database\Model\Database_Table_Model;
use Mantle\Queue\Database_Event;

/**
 * Queue Job Record
 *
 * A queued job stored in the `{prefix}mantle_queue` table. All `*_gmt` columns
 * are stored in UTC.
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
	 * Retrieve the table name for the model, without the site's table prefix.
	 */
	#[\Override]
	public static function get_table_name(): string {
		return 'mantle_queue';
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
		return Status::RUNNING->value === $this->status && now( 'UTC' )->isBefore( $this->get_lock_until() );
	}

	/**
	 * Get the lock end time.
	 */
	public function get_lock_until(): Carbon {
		return Carbon::parse( $this->available_at_gmt, 'UTC' );
	}

	/**
	 * Atomically claim the job for processing.
	 *
	 * Moves the job into the running state if it is either pending or a running
	 * job whose lock has already expired (left behind by a crashed worker). The
	 * `available_at_gmt <= now` guard combined with pushing the lock into the
	 * future ensures only one worker can claim a given job, even when multiple
	 * workers run concurrently.
	 *
	 * @param Carbon $lock_until The time until which the job should be locked.
	 * @param bool   $force      Claim a pending job even if it is not available yet.
	 * @return bool True if this process claimed the job, false if another won it.
	 */
	public function claim( Carbon $lock_until, bool $force = false ): bool {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$table = $wpdb->prefix . static::get_table_name();
		$lock  = $lock_until->copy()->setTimezone( 'UTC' )->toDateTimeString();

		$query = $force
			? $wpdb->prepare(
				"UPDATE {$table} SET status = %s, available_at_gmt = %s WHERE " . static::$primary_key . ' = %d AND status = %s', // phpcs:ignore WordPress.DB.PreparedSQL
				Status::RUNNING->value,
				$lock,
				$this->id,
				Status::PENDING->value,
			)
			: $wpdb->prepare(
				"UPDATE {$table} SET status = %s, available_at_gmt = %s WHERE " . static::$primary_key . ' = %d AND status IN ( %s, %s ) AND available_at_gmt <= %s', // phpcs:ignore WordPress.DB.PreparedSQL
				Status::RUNNING->value,
				$lock,
				$this->id,
				Status::PENDING->value,
				Status::RUNNING->value,
				now( 'UTC' )->toDateTimeString(),
			);

		if ( null === $query || ! $wpdb->query( $query ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			return false;
		}

		// Keep the in-memory model in sync with the row we just claimed.
		$this->status           = Status::RUNNING->value;
		$this->available_at_gmt = $lock;

		return true;
	}

	/**
	 * Start an attempt on a job this process has claimed, extending its lock.
	 *
	 * The current lock time doubles as an ownership token: if the lock expired and
	 * another worker reclaimed the job, the guarded update matches no rows. The
	 * attempt is counted here rather than at claim time because jobs are claimed
	 * in batches and some may never start.
	 *
	 * @param Carbon $lock_until The new time until which the job should be locked.
	 * @return bool True if the attempt started, false if the job is no longer owned.
	 */
	public function start_attempt( Carbon $lock_until ): bool {
		global $wpdb;

		assert( $wpdb instanceof \wpdb );

		$table = $wpdb->prefix . static::get_table_name();
		$lock  = $lock_until->copy()->setTimezone( 'UTC' )->toDateTimeString();
		$now   = now( 'UTC' )->toDateTimeString();

		$query = $wpdb->prepare(
			"UPDATE {$table} SET available_at_gmt = %s, last_attempt_gmt = %s, attempts = attempts + 1 WHERE " . static::$primary_key . ' = %d AND status = %s AND available_at_gmt = %s', // phpcs:ignore WordPress.DB.PreparedSQL
			$lock,
			$now,
			$this->id,
			Status::RUNNING->value,
			$this->available_at_gmt,
		);

		$updated = null === $query ? false : $wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $updated ) {
			return false;
		}

		if ( ! $updated ) {
			return false;
		}

		$this->available_at_gmt = $lock;
		$this->last_attempt_gmt = $now;
		$this->attempts         = (int) $this->attempts + 1;

		return true;
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
