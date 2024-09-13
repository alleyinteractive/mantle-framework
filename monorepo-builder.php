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

	$parameters->set( Option::DEFAULT_BRANCH_NAME, 'main' );

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
				'alleyinteractive/composer-wordpress-autoloader' => '^1.0',
				'php'                                            => '^8.2',
			],
			ComposerJsonSection::REQUIRE_DEV => [
				'alleyinteractive/alley-coding-standards' => '^1.0',
				'phpunit/phpunit'                         => '^9.3.3 || ^10.0.7 || ^11.0',
			],
		],
	);

	// Define the release order.
	$services = $container_config->services();

	// release workers - in order to execute.
	// $services->set( UpdateReplaceReleaseWorker::class );
	$services->set( SetCurrentMutualDependenciesReleaseWorker::class );
	$services->set( AddTagToChangelogReleaseWorker::class );
	$services->set( TagVersionReleaseWorker::class );
	$services->set( PushTagReleaseWorker::class );

	// todo: remove below services when going to 1.0.0.
	$services->set( SetNextMutualDependenciesReleaseWorker::class );
	$services->set( UpdateBranchAliasReleaseWorker::class );
	// $services->set( PushNextDevReleaseWorker::class );
};
