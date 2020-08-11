<?php
/**
 * Console_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Console\Generators\Command_Make_Command;
use Mantle\Framework\Console\Generators\Controller_Make_Command;
use Mantle\Framework\Console\Generators\Factory_Make_Command;
use Mantle\Framework\Console\Generators\Job_Make_Command;
use Mantle\Framework\Console\Generators\Model_Make_Command;
use Mantle\Framework\Console\Generators\Seeder_Make_Command;
use Mantle\Framework\Console\Generators\Service_Provider_Make_Command;
use Mantle\Framework\Console\Hook_Usage_Command;
use Mantle\Framework\Console\Package_Discover_Command;
use Mantle\Framework\Service_Provider;

/**
 * Console Service Provider
 *
 * Registers core commands for the framework.
 */
class Console_Service_Provider extends Service_Provider {
	/**
	 * Commands to register.
	 *
	 * @var array
	 */
	protected $commands_to_register = [
		Command_Make_Command::class,
		Controller_Make_Command::class,
		Factory_Make_Command::class,
		Hook_Usage_Command::class,
		Job_Make_Command::class,
		Model_Make_Command::class,
		Package_Discover_Command::class,
		Seeder_Make_Command::class,
		Service_Provider_Make_Command::class,
	];

	/**
	 * Register the commands.
	 *
	 * @return void
	 */
	public function register() {
		array_map( [ $this, 'add_command' ], $this->commands_to_register );
	}
}
