<?php
/**
 * Queue_Job class file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

use Mantle\Database\Model\Post;

/**
 * Queue Job Data Model (for internal use only).
 *
 * @access private
 */
class Queue_Job extends Post {
	/**
	 * Post type for the model.
	 *
	 * @var string
	 */
	public static $object_name = Provider::OBJECT_NAME;

	/**
	 * Check if the queue job is locked.
	 *
	 * @return bool True if the job is locked, false otherwise.
	 */
	public function is_locked(): bool {
		return $this->get_lock_until() > \time();
	}

	/**
	 * Get the lock end time.
	 *
	 * @return int The lock end time.
	 */
	public function get_lock_until(): int {
		return (int) ( $this->get_meta( Meta_Key::LOCK_UNTIL->value, true, ) ?? 0 );
	}

	/**
	 * Set the lock end time.
	 *
	 * @param int $lock_until The lock end time.
	 * @return void
	 */
	public function set_lock_until( int $lock_until ): void {
		$this->set_meta( Meta_Key::LOCK_UNTIL->value, $lock_until );
	}

	/**
	 * Clear the lock end time.
	 *
	 * @return void
	 */
	public function clear_lock(): void {
		$this->delete_meta( Meta_Key::LOCK_UNTIL->value );
	}

	/**
	 * Log an event for the job.
	 *
	 * @param string $event The event to log.
	 * @param array  $payload The event payload.
	 * @return void
	 */
	public function log( string $event, array $payload = [] ): void {
		$meta = $this->get_meta( Meta_Key::LOG->value );

		if ( ! is_array( $meta ) ) {
			$meta = [];
		}

		$meta[] = [
			'event'   => $event,
			'payload' => $payload,
			'time'    => \time(),
		];

		$this->set_meta( Meta_Key::LOG->value, $meta );
	}
}
