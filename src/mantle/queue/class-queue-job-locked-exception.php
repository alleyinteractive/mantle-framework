<?php
/**
 * Queue_Job_Locked_Exception class file
 *
 * @package Mantle
 */

namespace Mantle\Queue;

use Exception;
/**
 * Queue Job Locked Exception
 *
 * @todo Remove this if we don't use it.
 */
class Queue_Job_Locked_Exception extends Exception {
	/**
	 * Constructor.
	 *
	 * @param mixed $job The queue job.
	 */
	public function __construct( public mixed $job ) {
		parent::__construct( 'Queue job is locked: ' . ( is_object( $job ) ? $job::class : gettype( $job ) ) );
	}
}
