<?php
/**
 * Monorepo Builder
 * For internal use only.
 *
 * phpcs:disable
 *
 * @package Mantle
 */

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\AddTagToChangelogReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushNextDevReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushTagReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetNextMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\TagVersionReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateBranchAliasReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateReplaceReleaseWorker;
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
			ComposerJsonSection::REQUIRE     => [
				'alleyinteractive/composer-wordpress-autoloader' => '^0.2',
				'php' => '^7.4|^8.0',
			],
			ComposerJsonSection::REQUIRE_DEV => [
				'alleyinteractive/alley-coding-standards' => '^0.3',
				'phpunit/phpunit'                         => '^8.5.8 || ^9.3.3',
			],
		],
	);

	// Define the release order.
	$services = $container_config->services();

	// release workers - in order to execute.
	$services->set( UpdateReplaceReleaseWorker::class );
	$services->set( SetCurrentMutualDependenciesReleaseWorker::class );
	$services->set( AddTagToChangelogReleaseWorker::class );
	$services->set( TagVersionReleaseWorker::class );
	$services->set( PushTagReleaseWorker::class );
	$services->set( SetNextMutualDependenciesReleaseWorker::class );
	$services->set( UpdateBranchAliasReleaseWorker::class );
	$services->set( PushNextDevReleaseWorker::class );
};