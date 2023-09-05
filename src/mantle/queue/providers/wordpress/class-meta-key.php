<?php
/**
 * Meta_Key enum file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

/**
 * Meta keys for storage of queue data.
 */
enum Meta_Key: string {
	/**
	 * Storage of the job data.
	 */
	case JOB = '_mantle_queue';

	/**
	 * Storage of the job failure.
	 */
	case FAILURE = '_mantle_queue_failure';

	/**
	 * Storage of the job start time.
	 */
	case START_TIME = '_mantle_queue_start_time';

	/**
	 * Storage of the job lock end time.
	 */
	case LOCK_UNTIL = '_mantle_queue_lock_until';
}
