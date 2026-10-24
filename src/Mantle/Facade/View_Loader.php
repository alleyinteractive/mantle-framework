<?php
/**
 * View_Loader Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * View_Loader Facade
 *
 * @method static \Mantle\Http\View\View_Finder add_extension(string $extension)
 * @method static string[] get_extensions()
 * @method static \Mantle\Http\View\View_Finder add_path(string $path, string $alias = null)
 * @method static \Mantle\Http\View\View_Finder remove_path(string $path)
 * @method static array<string> get_paths()
 * @method static \Mantle\Http\View\View_Finder clear_paths()
 * @method static string find(string $slug, string $name = null)
 * @method static bool has_hint_information(string $alias)
 *
 * @see \Mantle\Http\View\View_Finder
 */
class View_Loader extends Facade {
	/**
	 * Get the registered name of the component.
	 */
	protected static function get_facade_accessor(): string {
		return 'view.loader';
	}
}
