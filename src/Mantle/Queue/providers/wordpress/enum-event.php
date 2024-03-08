<?php
/**
 * Events enum file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress;

/**
 * Events for the jobs on the queue
 */
enum Event: string {
	case STARTING = 'starting';
	case FINISHED = 'finished';
	case FAILED   = 'failed';
	case RETRYING = 'retrying';
}
