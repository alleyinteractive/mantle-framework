<?php
/**
 * Status enum file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Jobs;

/**
 * Queue Job Statuses
 */
enum Status: string {
		case PENDING   = 'pending';
		case RUNNING   = 'running';
		case FAILED    = 'failed';
		case COMPLETED = 'completed';
}
