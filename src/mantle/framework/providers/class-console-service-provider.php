<?php
/**
 * Console_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Console\Clear_Cache_Command;
use Mantle\Framework\Console\Config_Cache_Command;
use Mantle\Framework\Console\Config_Clear_Command;
use Mantle\Framework\Console\Generators\Command_Make_Command;
use Mantle\Framework\Console\Generators\Controller_Make_Command;
use Mantle\Framework\Console\Generators\Factory_Make_Command;
use Mantle\Framework\Console\Generators\Generator_Make_Command;
use Mantle\Framework\Console\Generators\Job_Make_Command;
use Mantle\Framework\Console\Generators\Middleware_Make_Command;
use Mantle\Framework\Console\Generators\Model_Make_Command;
use Mantle\Framework\Console\Generators\Seeder_Make_Command;
use Mantle\Framework\Console\Generators\Service_Provider_Make_Command;
use Mantle\Framework\Console\Hook_Usage_Command;
use Mantle\Framework\Console\Package_Discover_Command;
use Mantle\Framework\Console\Route_List_Command;
use Mantle\Framework\Console\View_Cache_Command;
use Mantle\Framework\Console\View_Clear_Command;
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
		Clear_Cache_Command::class,
		Command_Make_Command::class,
		Config_Cache_Command::class,
		Config_Clear_Command::class,
		Controller_Make_Command::class,
		Factory_Make_Command::class,
		Generator_Make_Command::class,
		Hook_Usage_Command::class,
		Job_Make_Command::class,
		Middleware_Make_Command::class,
		Model_Make_Command::class,
		Package_Discover_Command::class,
		Route_List_Command::class,
		Seeder_Make_Command::class,
		Service_Provider_Make_Command::class,
		View_Cache_Command::class,
		View_Clear_Command::class,
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
