<?php
/**
 * Monorepo Builder
 *
 * For internal use only.
 */

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symplify\MonorepoBuilder\ValueObject\Option;

return static function ( ContainerConfigurator $container_config ): void {
	$parameters = $container_config->parameters();

	// Define the location of the packages.
	$parameters->set(
		Option::PACKAGE_DIRECTORIES,
		[
			__DIR__ . '/src/mantle',
		],
	);

	// Ignore specific packages.
	$parameters->set(
		Option::PACKAGE_DIRECTORIES_EXCLUDES,
		[
			__DIR__ . '/src/mantle/framework',
		],
	);

	// Append specific items to the composer.json.
	$parameters->set(
		Option::DATA_TO_APPEND,
		[
			ComposerJsonSection::REQUIRE => [
				'alleyinteractive/composer-wordpress-autoloader' => '^0.2',
				'php'                                            => '^7.4|^8.0',
			],
			ComposerJsonSection::REQUIRE_DEV => [
				'alleyinteractive/alley-coding-standards' => '^0.3',
				'phpunit/phpunit'                         => '^8.5.8 || ^9.3.3',
			],
		],
	);

	// Remove the mantle packages from the composer.json (temporary).
	// $parameters->set(
	// 	Option::DATA_TO_REMOVE,
	// 	[
	// 		ComposerJsonSection::REQUIRE => [
	// 			'mantle-framework/assets' => '*',
	// 			'mantle-framework/auth' => '*',
	// 			'mantle-framework/cache' => '*',
	// 			'mantle-framework/caper' => '*',
	// 			'mantle-framework/config' => '*',
	// 			'mantle-framework/console' => '*',
	// 			'mantle-framework/container' => '*',
	// 			'mantle-framework/contracts' => '*',
	// 			'mantle-framework/database' => '*',
	// 			'mantle-framework/events' => '*',
	// 			'mantle-framework/facade' => '*',
	// 			'mantle-framework/faker' => '*',
	// 			'mantle-framework/filesystem' => '*',
	// 			'mantle-framework/http' => '*',
	// 			'mantle-framework/log' => '*',
	// 			'mantle-framework/query-monitor' => '*',
	// 			'mantle-framework/queue' => '*',
	// 			'mantle-framework/rest-api' => '*',
	// 			'mantle-framework/scheduling' => '*',
	// 			'mantle-framework/support' => '*',
	// 			'mantle-framework/testing' => '*',
	// 			'mantle-framework/view' => '*',
	// 		],
	// 	],
	// );
};
