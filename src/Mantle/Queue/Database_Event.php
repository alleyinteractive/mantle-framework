<?php
/**
 * Database_Event enum file
 *
 * @package Mantle
 */

namespace Mantle\Queue;

/**
 * Events for database jobs on the queue
 */
enum Database_Event: string {
	case STARTING = 'starting';
	case FINISHED = 'finished';
	case FAILED   = 'failed';
	case RETRYING = 'retrying';
}
