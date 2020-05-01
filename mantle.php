<?php
/**
 * Plugin Name: Mantle
 * Plugin URI:  https://github.com/alleyinteractive/mantle
 * Description: A framework for powerful WordPress development
 * Author:      Alley
 * Author URI:  https://alley.co/
 * Text Domain: mantle
 * Domain Path: /languages
 * Version:     0.1
 *
 * @package Mantle
 */

namespace Mantle;

use Mantle\Framework\Database\Console\SeedCommand;

require_once __DIR__ . '/vendor/autoload.php';

new SeedCommand();
