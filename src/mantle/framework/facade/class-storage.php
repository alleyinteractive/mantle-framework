<?php
/**
 * Storage Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Facade;

/**
 * Storage Facade
 *
 * @mixin \Mantle\Framework\Filesystem\Filesystem_Manager
 */
class Storage extends Facade {
	/**
	 * Facade Accessor
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'filesystem';
	}
}
