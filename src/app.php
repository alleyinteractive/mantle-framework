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

// todo: setup http kernel.

// $mantle_app->singleton(
// 	Mantle\Framework\Contracts\Http\Kernel::class,
// 	// Http class.
// );

return $mantle_app;
