<?php
namespace Mantle\Framework\Console;

use WP_CLI;

abstract class Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (defined('WP_CLI') && WP_CLI) {
            if (empty($this->name)) {
                throw new InvalidCommandException('Command missing name.');
            }

            WP_CLI::add_command('mantle ' . $this->name, [$this, 'handle']);
        }
    }

    /**
     * Handler for the command.
     */
    abstract public function handle();
}
