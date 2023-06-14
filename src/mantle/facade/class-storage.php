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
 * @method static \Mantle\Contracts\Filesystem\Filesystem drive(string $name = null)
 * @method static \Mantle\Filesystem\Filesystem_Manager extend(string $driver, Closure $callback)
 * @method static \Mantle\Contracts\Filesystem\Filesystem create_local_driver(array $config)
 * @method static \Mantle\Filesystem\Filesystem_Adapter create_s3_driver(array $config)
 * @method static string[] all_directories(string $directory = null)
 * @method static array directories(string $directory = null, bool $recursive = false)
 * @method static bool make_directory(string $path)
 * @method static bool delete_directory(string $directory)
 * @method static string[] all_files(string $directory = null)
 * @method static string[] files(string $directory = null, bool $recursive = false)
 * @method static bool copy(string $from, string $to)
 * @method static bool move(string $from, string $to)
 * @method static bool delete(string|string[] $paths)
 * @method static bool exists(string $path)
 * @method static bool missing(string $path)
 * @method static string|bool get(string $path)
 * @method static int|bool last_modified(string $path)
 * @method static bool put(string $path, string|resource $contents, array|string $options = [])
 * @method static int|bool size(string $path)
 * @method static resource|false read_stream(string $path)
 * @method static bool write_stream(string $path, resource $resource, array|string $options = [])
 * @method static bool prepend(string $path, string $data, string $separator = '\n')
 * @method static bool append(string $path, string $data, string $separator = '\n')
 * @method static string get_visibility(string $path)
 * @method static bool set_visibility(string $path, string $visibility)
 * @method static string|null url(string $path)
 * @method static string temporary_url(string $path, \DateTimeInterface $expiration, array $options = [])
 *
 * @see \Mantle\Filesystem\Filesystem_Manager
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
