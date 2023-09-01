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
	case PENDING = 'pending';
	case RUNNING = 'running';
	case FAILED = 'failed';
}
