<?php
/**
 * Action class file
 *
 * @package Mantle
 */

namespace Mantle\Support\Attributes;

use Attribute;

/**
 * Hook Action Attribute
 *
 * Used to hook a method to an WordPress action at a specific priority.
 */
#[Attribute]
class Action {
	/**
	 * Constructor.
	 *
	 * @param string $action Action name.
	 * @param int    $priority Priority, defaults to 10.
	 */
	public function __construct( public string $action, public int $priority = 10 ) {}
}
