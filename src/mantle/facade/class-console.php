<?php
/**
 * Console Facade class file
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Closure;
use Mantle\Console\Closure_Command;
use Mantle\Contracts\Console\Kernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Console Facade
 *
 * @method static int handle(\Symfony\Component\Console\Input\InputInterface $input = null, \Symfony\Component\Console\Output\OutputInterface $output = null)
 * @method static int call(string $command, array $parameters = [], mixed $output_buffer = null)
 * @method static \Symfony\Component\Console\Tester\CommandTester test(string $command, array $parameters = [])
 * @method static \Mantle\Console\Closure_Command command(string $signature, Closure $callback)
 * @method static void bootstrap()
 * @method static \Mantle\Contracts\Console\Application get_console_application()
 * @method static void set_console_application(\Mantle\Contracts\Console\Application $app)
 * @method static void commands()
 * @method static void register_commands()
 * @method static void log(string $message)
 * @method static void terminate(\Symfony\Component\Console\Input\InputInterface $input, int $status)
 * @method static string[] classes_from_path(string $path, string $root_namespace)
 * @method static string|null classname_from_path(SplFileInfo $file, string $root_namespace)
 *
 * @see \Mantle\Framework\Console\Kernel
 */
class Console extends Facade {
	/**
	 * Facade Accessor
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return Kernel::class;
	}
}
