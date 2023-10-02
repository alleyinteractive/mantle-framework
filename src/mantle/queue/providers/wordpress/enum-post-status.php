<?php
/**
 * Post_Status enum file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

/**
 * Post Statuses for jobs on the queue
 */
enum Post_Status: string {
	case PENDING   = 'queue_pending';
	case RUNNING   = 'queue_running';
	case FAILED    = 'queue_failed';
	case COMPLETED = 'queue_completed';
}
