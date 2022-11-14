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
 * @method static int handle(InputInterface $input = null, OutputInterface $output = null)
 * @method static int call(string $command, array $parameters = [], OutputInterface $output = null)
 * @method static CommandTester test(string $command, array $parameters = [])
 * @method static Closure_Command command(string $signature, Closure $callback)
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
