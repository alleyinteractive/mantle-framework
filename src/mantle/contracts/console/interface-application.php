<?php
namespace Mantle\Contracts\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Application {
	/**
	 * Run the console application.
	 *
	 * @return int
	 */

	/**
	 * Run the command through the console application.
	 *
	 * @param InputInterface|null  $input Input interface.
	 * @param OutputInterface|null $output Output interface.
	 * @return int
	 */
	public function run( InputInterface $input = null, OutputInterface $output = null ): int;
}
