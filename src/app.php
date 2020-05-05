<?php
/**
 * Mantle Application
 *
 * @package Mantle
 */

$mantle_app = new Mantle\Framework\Application();

/**
 * Register the main contracts that power the application.
 */
$mantle_app->singleton(
	Mantle\Framework\Contracts\Console\Kernel::class,
	Mantle\Framework\Console\Kernel::class,
);

$mantle_app->singleton(
	Mantle\Framework\Contracts\Http\Kernel::class,
	Mantle\Framework\Http\Kernel::class,
);

return $mantle_app;
