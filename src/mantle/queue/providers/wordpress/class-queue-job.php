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
}
