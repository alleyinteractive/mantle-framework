<?php
/**
 * Asset Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Asset
 *
 * @method static \Mantle\Assets\Asset script(void ...$params)
 * @method static \Mantle\Assets\Asset style(void ...$params)
 * @method static void preload(string $handle, string $src, string $condition = 'global', string|null $as = null, string|null $mime_type = null, string $media = 'all', bool $crossorigin = false, string|null $version = null)
 * @method static void async(string $handle)
 * @method static void defer(string $handle)
 * @method static void load_method(string $handle, string $load_method = 'sync')
 *
 * @see \Mantle\Assets\Asset_Manager
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
