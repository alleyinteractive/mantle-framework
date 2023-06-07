<?php
/**
 * View Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * View
 *
 * @method static string get_path()
 * @method static \Mantle\Http\View\View set_post(\Mantle\Database\Model\Post|\WP_Post|int $post)
 * @method static \Mantle\Http\View\View with(string|array $key, mixed $value = null)
 * @method static array get_variables()
 * @method static mixed get_variable(string $key, mixed $default = null)
 * @method static \Mantle\Http\View\View cache(int|bool $cache_ttl = 900, string $cache_key = null)
 * @method static string get_cache_key()
 * @method static string render()
 *
 * @see \Mantle\Http\View\View
 */
class View extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return 'view';
	}
}
