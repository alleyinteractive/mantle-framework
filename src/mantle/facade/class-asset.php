<?php
/**
 * Asset Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Asset Facade
 *
 * @mixin \Mantle\Assets\Asset_Manager
 */
class Asset extends Facade {
	/**
	 * Facade Accessor
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'asset.manager';
	}
}
