<?php
namespace Mantle\Framework\Database\Console;

use Mantle\Framework\Console\Command;

/**
 * Database Seed Command
 */
class SeedCommand extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Example description';

    /**
     * Run Database Seeding
     */
    public function handle()
    {
        \WP_CLI::log( 'Handle..' );
    }
}
