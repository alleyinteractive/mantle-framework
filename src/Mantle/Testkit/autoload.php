<?php
/**
 * Testkit autoload file
 *
 * @package Mantle
 */

use NunoMaduro\Collision\Adapters\Phpunit\Subscribers\EnsurePrinterIsRegisteredSubscriber;

/**
 * Register the collision printer.
 *
 * Due to a change in the collision package, the printer must be registered
 * manually unless the test suite is manually invoked by the code base (which
 * most projects do not use).
 *
 * The printer code is largely private and not intended to be used by external
 * code, so we need to carefully check for the existence of the classes and
 * methods we need to use.
 */
if (
	PHP_SAPI === 'cli'
	&& defined( 'PHPUNIT_COMPOSER_INSTALL' )
	&& empty( getenv( 'COLLISION_DISABLE' ) ) // A kill switch for disabling the printer.
	&& class_exists( EnsurePrinterIsRegisteredSubscriber::class )
	&& method_exists( EnsurePrinterIsRegisteredSubscriber::class, 'register' ) // @phpstan-ignore-line already
) { // phpcs:ignore WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeCloseParenthesis
	$_SERVER['COLLISION_PRINTER'] = 'DefaultPrinter';

	EnsurePrinterIsRegisteredSubscriber::register();
}
