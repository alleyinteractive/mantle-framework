<?php
/**
 * Storage Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Storage Facade
 *
 * @mixin \Mantle\Filesystem\Filesystem_Manager
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
